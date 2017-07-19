<?php
namespace Plugin;

class App {
	
	private $machine;
	
	function __construct($machine) {
		$this->machine = $machine;
	}
	
	/**
	 *	define sportrights
	 */
	public function createLeagues($n_leagues, $teams_per_league) {
		//
	}
	
	/**
	 *	starting from teams and their sportright compose standings. 
	 */
	public function createStandings() {
		/*
		for ($i = 0; $i < self::N_LEAGUES; $i++) {
			// create league
			$leaguename = "League " . ($i + 1);
			$league = $this->machine->plugin("Database")->addItem('league', [
				"name" => $leaguename,
				"slug" => $this->machine->slugify($leaguename)
			]);
			
			// look for teams with sportright to play in the league
			$teams = $this->machine->plugin("Database")->find('team', 'sportright = ?', [$i+1]);
			// compose standings
			foreach ($teams as $team) {
				$this->machine->plugin("Database")->addItem('standing', [
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
		*/
	}
	
	public function createFixtures() {
		//
	}
}
