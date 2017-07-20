<?php
namespace Plugin;

class App {
	
	private $machine;
	private $db;
	
	private $league_names = ["Serie A", "Serie B", "Lega Pro", "Campionato Nazionale Dilettanti"];
	
	/**
	 *	fixtures matrixes
	 */
	private $fixtures = [
		"10" => [
			[[1,10],[2,9],[3,8],[4,7],[5,6]],
			[[10,6],[7,5],[8,4],[9,3],[1,2]],
			[[2,10],[3,1],[4,9],[5,8],[6,7]],
			[[10,7],[8,6],[9,5],[1,4],[2,3]],
			[[3,10],[4,2],[5,1],[6,9],[7,8]],
			[[10,8],[9,7],[1,6],[2,5],[3,4]],
			[[4,10],[5,3],[6,2],[7,1],[8,9]],
			[[10,9],[1,8],[2,7],[3,6],[4,5]],
			[[5,10],[6,4],[7,3],[8,2],[9,1]]
		]
	];
	
	function __construct($machine) {
		$this->machine = $machine;
		$this->db = $this->machine->plugin("Database");
	}
	
	/**
	 *	create leagues
	 *
	 *	@param $n_leagues Integer The number of leagues to create.
	 */
	public function createLeagues($n_leagues) {
		for ($i = 0; $i < $n_leagues; $i++) {
			// define league name
			$leaguename = "League " . ($i + 1);
			if (isset($this->league_names[$i])) {
				$leaguename = $this->league_names[$i];
			}
			
			// save league in db
			$league = $this->db->addItem('league', [
				"name" => $leaguename,
				"slug" => $this->machine->slugify($leaguename),
				"level" => ($i + 1)
			]);
		}
	}
	
	/**
	 *	assign sportrights
	 */
	public function assignSportrights($teams_per_league) {
		$leagues = $this->db->find("league", "ORDER BY level ASC");
		$teams = array_values($this->db->find("team", "ORDER BY prestige DESC, RAND()"));

		$team_cont = 0;
		$this->db->exec("UPDATE team SET sportright = 0");
		foreach ($leagues as $league) {
			for ($i = 0; $i < $teams_per_league; $i++) {
				$teams[$team_cont]->sportright = $league->level;
				$this->db->update($teams[$team_cont]);
				$team_cont++;
			}
		}
	}
	
	/**
	 *	based on teams and their sportright compose standings. 
	 */
	public function createStandings() {
		$leagues = $this->db->find("league", "ORDER BY level ASC");
		foreach ($leagues as $league) {
			// look for teams with sportright to play in the league
			$teams = $this->db->find('team', 'sportright = ?', [$league->level]);
			// compose standings
			foreach ($teams as $team) {
				$this->db->addItem('standing', [
					"team" => $team,
					"league" => $league,
					"played" => 0,
					"won" => 0,
					"draw" => 0,
					"lost" => 0,
					"goalscored" => 0,
					"goalconceded" => 0,
					"points" => 0
				]);
			}
		}
	}

	public function createFixtures($scheduled_turn = 0) {
		$leagues = $this->db->find("league", "ORDER BY level ASC");
		foreach ($leagues as $league) {
			$standings = array_values($this->getStandings($league->level));
			shuffle($standings);
			$fixtures = $this->fixtures[10];
			for ($i = 0; $i < count($fixtures); $i++) {
				for ($j = 0; $j < count($fixtures[$i]); $j++) {
					$this->db->addItem('match', [
						"round" => $i + 1,
						"scheduledturn" => $scheduled_turn + (($i + 1) *7),
						"league" => $league,
						"team1" => $standings[$fixtures[$i][$j][0]-1]->team,
						"team2" => $standings[$fixtures[$i][$j][1]-1]->team,
						"goal1" => 0,
						"goal2" => 0,
						"played" => false
					]);
				}
			}
		}
	}
	
	public function getStandings($league_level) {
		return $this->db->find("standing", "league_id = ? ORDER BY points DESC", [$league_level]);
	}
	
	public function getNextMatches($league_level) {
		return $this->db->find("match", "league_id = ? AND played = 0 AND scheduledturn = (SELECT MIN(scheduledturn) FROM `match` WHERE played = 0)", [$league_level]);
	}
	
	public function getFullCalendar($league_level) {
		return $this->db->find("match", "league_id = ?", [$league_level]);
	}
	
	public function setOption($optkey, $optvalue) {
		$bean = $this->db->getItemByField("option", "optkey", $optkey);
		$bean->optvalue = $optvalue;
		$this->db->update($bean);
	}
	
	public function checkTime() {
		$current_turn = intval($this->GetOption("turn"));
		$time_passed = time() - strtotime($this->GetOption("gameStartedAt"));
		$target_turn = floor($time_passed / (intval($this->GetOption("turnLengthMinutes")) * 60));
		$turns_to_pass = $target_turn - $current_turn;
		if ($turns_to_pass == 0) {
			return;
		}
		if ($turns_to_pass > 0) {
			for ($i = 0; $i < $turns_to_pass; $i++) {
				$this->passTurn();
			}
		}
	}
	
	private function passTurn() {
		$turn = intval($this->GetOption("turn"));
		$this->setOption("turn", $turn + 1);
		$this->updateMatches();
	}
	
	private function updateMatches() {
		// find scheduled matches for today
		$matches = $this->db->find("match", "played = 0 AND scheduledturn = ?", [intval($this->GetOption("turn"))]);
		foreach ($matches as $match) {
			$this->playMatch($match);
		}
	}
	
	private function playMatch($match) {
		$team1 = $match->fetchAs("team")->team1;
		$team2 = $match->fetchAs("team")->team2;
		
		// weights are related to team 1 probability of win
		$weigths = [
			"suffer" => 90,	// keeper vs opponent attackers
			"defend" => 80, // defenders vs opponent attackers
			"middle" => 50, // midfielders
			"attack" => 20, // attackers vs opponent defenders
			"score" => 10		// attackers vs opponent keeper
		];
		
		// modify weight based on team values
		$s1 = $team1->strenght;
		$s2 = $team2->strenght;
		$f1 = $team1->form;
		$f2 = $team2->form;
		$p1 = $this->getPercentOf($s1, $f1);
		$p2 = $this->getPercentOf($s2, $f2);
		$weights["middle"] = $this->getPercentOf($p1, $p1 + $p2);
		
		// define the state machine
		$transitions = [
			"middle" => ["defend", "attack"],
			"defend" => ["suffer", "middle"],
			"attack" => ["middle", "score"],
			"suffer" => [-1, "defend"],
			"score" => ["attack", 1]
		];
		
		$current_state = "middle";
		$goal_1 = 0;
		$goal_2 = 0;
		//$this->machine->plugin("Log")->log($team1->teamname . " - " . $team2->teamname, "Match begins!");
		for ($i = 0; $i < 90; $i++) {
			$p = $weigths[$current_state];
			$result = $this->event($p);
			$next_state = $result ? $transitions[$current_state][1] : $transitions[$current_state][0];
			//$this->machine->plugin("Log")->log($team1->teamname . " - " . $team2->teamname, $next_state);
			if ($next_state == -1) {
				$goal_2++;
				$current_state = "middle";
			} elseif ($next_state == 1) {
				$goal_1++;
				$current_state = "middle";
			} else {
				$current_state = $next_state;
			}
		}
		
		// update match
		$match->played = true;
		$match->goal1 = $goal_1;
		$match->goal2 = $goal_2;
		$this->db->update($match);
		
		// update standings
		$standing1 = $this->db->findOne("standing", "team_id = ?", [$team1->id]);
		$standing2 = $this->db->findOne("standing", "team_id = ?", [$team2->id]);
		
		$standing1->played++;
		$standing2->played++;
		$standing1->goalscored += $goal_1;
		$standing2->goalscored += $goal_2;
		$standing1->goalconceded += $goal_2;
		$standing2->goalconceded += $goal_1;	
		if ($goal_1 > $goal_2) {
			$standing1->won++;
			$standing2->lost++;
			$standing1->points += 3;
		} elseif($goal_2 > $goal_1) {
			$standing1->lost++;
			$standing2->won++;
			$standing2->points += 3;
		} else {
			$standing1->draw++;
			$standing2->draw++;			
			$standing1->points += 1;
			$standing2->points += 1;
		}
		$this->db->update($standing1);
		$this->db->update($standing2);
	}
	
	private function event($perc) {
		return mt_rand(1, 100) < $perc;
	}
	
	private function getPercentOf($n, $perc) {
		return ($perc * $n) / 100;
	}
	
	// tags
	
	public function GetOption($params) {
		if (gettype($params) == "string") {
			$params = [$params];
		}
		$optkey = $params[0];
		$bean = $this->db->getItemByField("option", "optkey", $optkey);
		return $bean->optvalue;
	}
}
