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

	public function createFixtures() {
		$leagues = $this->db->find("league", "ORDER BY level ASC");
		foreach ($leagues as $league) {
			$standings = array_values($this->getStandings($league->level));
			shuffle($standings);
			$fixtures = $this->fixtures[10];
			for ($i = 0; $i < count($fixtures); $i++) {
				for ($j = 0; $j < count($fixtures[$i]); $j++) {
					$this->db->addItem('match', [
						"round" => $i + 1,
						"league" => $league,
						"team1" => $standings[$fixtures[$i][$j][0]-1]->team,
						"team2" => $standings[$fixtures[$i][$j][1]-1]->team,
						"goal1" => 0,
						"goal2" => 0
					]);
				}
			}
		}
	}
	
	public function getStandings($league_level) {
		return $this->db->find("standing", "league_id = ?", [$league_level]);
	}

}
