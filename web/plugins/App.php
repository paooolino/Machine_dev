<?php
namespace Plugin;

class App {
	
	private $machine;
	private $db;
	
	private $league_names = ["Serie A", "Serie B", "Lega Pro", "Campionato Nazionale Dilettanti"];
	
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
		//
	}
}
