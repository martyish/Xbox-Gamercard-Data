<?php

class XboxGamercard {
	/**
	 * The gamertag to get data for
	 *
	 * @var string
	 */
	public $gamertag;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
	}

	/**
	 * Parse out the TID from a URL
	 *
	 * @param string
	 * @return string
	 */
	private function get_tid( $string )
	{
		$tid = parse_url( $string );
		$tid = explode( '&', html_entity_decode( $tid['query'] ) );
		$tid = explode( '=', $tid['0'] );
		
		return $tid['1'];
	}

	/**
	 * Parse the entire data set for the gamertag provided
	 *
	 * @param object The cURL object
	 * @return object
	 */
	public function parse_data($object) {
		$xbox_data = new stdClass();

		/**
		 * Properly formatted Gamertag
		 * @return string
		 */
		preg_match( '~<a id="Gamertag" href=".*?">(.*?)</a>~si', $object, $gamertag);
		$xbox_data->gamertag = $gamertag[1];

		/**
		 * Check if the gamertag is valid
		 * @return bool
		 */
		preg_match('~<img id="Gamerpic" src="(.*?)" alt~si', $object, $avatar);
		$xbox_data->is_valid = $avatar[1] === 'http://image.xboxlive.com//global/t.FFFE07D1/tile/0/20000' ? 0 : 1;

		/**
		 * URL to Xbox.com profile
		 * @return string
		 */
		$xbox_data->profile_link = 'http://live.xbox.com/member/' . rawurlencode($gamertag[1]);

		/**
		 * Check to see if they were part of the Xbox 360 Launch Team
		 * @return bool
		 */
		$xbox_data->launch_team_xbl = preg_match('~<div id="Xbc360Launch"~', $object) ? 1 : 0;

		/**
		 * Check to see if they were part of the NXE Launch Team
		 * @return bool
		 */
		$xbox_data->launch_team_nxe = preg_match('~<div id="XbcNXELaunch"~', $object) ? 1 : 0;

		/**
		 * Check to see if they were part of the Kinect Launch Team
		 * @return bool
		 */
		$xbox_data->launch_team_kinect = preg_match('~<div id="XbcKinectLaunch"~', $object) ? 1 : 0;

		/**
		 * Check to see if their account is Silver or Gold
		 * @return string
		 */
		preg_match('~<div class="XbcGamercard (.*?)">~si', $object, $status);
		$xbox_data->account_status = preg_match('~Gold~si', $status[1]) ? 'Gold' : 'Silver';

		/**
		 * The gender of the given gamertag based on their avatar's gender
		 * @return string
		 */
		preg_match('~<div class="XbcGamercard (.*?)">~si', $object, $gender);
		$xbox_data->gender = preg_match('~Male~si', $gender[1]) ? 'Male' : 'Female';

		/**
		 * Check to see if they were labeled a cheater by Xbox
		 * @return bool
		 */
		preg_match('~<div class="XbcGamercard (.*?)">~si', $object, $cheater);
		$xbox_data->is_cheater = preg_match('~Cheater~si', $cheater[1]) ? 1 : 0;

		/**
		 * Returns all forms of avatars for the given gamertag
		 * @return string
		 */
		$xbox_data->avatar_tile = $avatar[1];
		$xbox_data->avatar_small = 'http://avatar.xboxlive.com/avatar/' . rawurlencode($gamertag[1]) . '/avatarpic-s.png';
		$xbox_data->avatar_large = 'http://avatar.xboxlive.com/avatar/' . rawurlencode($gamertag[1]) . '/avatarpic-l.png';
		$xbox_data->avatar_body = 'http://avatar.xboxlive.com/avatar/' . rawurlencode($gamertag[1]) . '/avatar-body.png';

		/**
		 * The reputation based on a total of 100
		 * @return int
		 */
		preg_match_all('~<div class="Star (.*?)">~si', $object, $rep);
		$total_rep = 0;
		foreach ($rep[1] as $k => $v)
		{
			$starvalue = array(
				'Empty' => 0,
				'Quarter' => 5,
				'Half' => 10,
				'ThreeQuarter' => 15,
				'Full' => 20
			);

			$total_rep = $total_rep + $starvalue[$v];
		}
		$xbox_data->reputation = (int)$total_rep;

		/**
		 * The gamerscore for the requested gamertag
		 * @return int
		 */
		preg_match('~<div id="Gamerscore">(.*?)</div>~si', $object, $gamerscore );
		$xbox_data->gamerscore = (int)$gamerscore[1];

		/**
		 * The location as provided in the requested gamertag's profile
		 * @return string
		 */
		preg_match('~<div id="Location">(.*?)</div>~si', $object, $location);
		$xbox_data->location = (string)$location[1];

		/**
		 * The motto as provided in the requested gamertag's profile
		 * @return string
		 */
		preg_match('~<div id="Motto">(.*?)</div>~si', $object, $motto);
		$xbox_data->motto = (string)$motto[1];

		/**
		 * The name as provided in the requested gamertag's profile
		 * @return string
		 */
		preg_match('~<div id="Name">(.*?)</div>~si', $object, $name);
		$xbox_data->name = (string)$name[1];

		/**
		 * The bio as provided in the requested gamertag's profile
		 * @return string
		 */
		preg_match( '~<div id="Bio">(.*?)</div>~si', $object, $bio );
		$xbox_data->bio = (string)$bio[1];

		/**
		 * The 5 recently played games 
		 */
		if (!preg_match('~<ol id="PlayedGames" class="NoGames">~', $object)) {
			preg_match('~<ol id="PlayedGames" >(.*?)</ol>~si', $object, $recent_games);
			preg_match_all('~<li.*?>.*?<a href="(.*?)">.*?<img src="(.*?)" alt=".*?" title=".*?" />.*?<span class="Title">(.*?)</span>.*?<span class="LastPlayed">(.*?)</span>.*?<span class="EarnedGamerscore">(.*?)</span>.*?<span class="AvailableGamerscore">(.*?)</span>.*?<span class="EarnedAchievements">(.*?)</span>.*?<span class="AvailableAchievements">(.*?)</span>.*?<span class="PercentageComplete">(.*?)%</span>.*?</a>.*?</li>~si', $recent_games[1], $lastplayed, PREG_SET_ORDER);

			$i = 1;
			foreach ($lastplayed as $recent_game)
			{
				$obj = new stdClass();

				/**
				 * The game title
				 * @return string
				 */
				$obj->title = (string)$recent_game[3];

				/**
				 * The game TID, Xbox's title id for a given game
				 * @return int
				 */
				$obj->tid = (int)$this->get_tid($recent_game[1]);

				/**
				 * URL to the game on Xbox.com Marketplace
				 * @return string
				 */
				$obj->marketplace_url = (string)'http://marketplace.xbox.com/Title/' . $this->get_tid($recent_game[1]);

				/**
				 * URL to compare stats for a given game with another gamertag (must be logged into Xbox.com)
				 * @return string
				 */
				$obj->compare_url = (string)$recent_game[1];

				/**
				 * The URL to the game's tile image
				 * @return string
				 */
				$obj->image = (string)$recent_game[2];

				/**
				 * The date the game was last played in mm/dd/yyyy format
				 * @return string
				 */
				$obj->last_played = (string)$recent_game[4];

				/**
				 * The amount of gamerscore earned in the game
				 * @return int
				 */
				$obj->earned_gamerscore = (int)$recent_game[5];

				/**
				 * The total amount of gamerscore available in a game
				 * @return int
				 */
				$obj->available_gamerscore = (int)$recent_game[6];

				/**
				 * The number of achievements earned in a game
				 * @return int
				 */
				$obj->earned_achievements = (int)$recent_game[7];

				/**
				 * The total number of available achievements in a game
				 * @return int
				 */
				$obj->available_achievements = (int)$recent_game[8];

				/**
				 * The percent completed of a game
				 * @return int
				 */
				$obj->percentage_complete = (int)$recent_game[9];

				$xbox_data->recent_games[$i] = $obj;
				++$i;
			}
		} else {
			$xbox_data->recent_games = false;
		}

		return $xbox_data;
	}

	/**
	 * Build the request to scrape data from Xbox.com
	 *
	 * @param string $gamertag The gamertag to get data from
	 * @param string $region The region for localization, defaults to en-US
	 * @return object
	 */
	public function build_request($gamertag, $region = 'en-US') {
		/* Make sure the gamertag is properly formatted */
		if (!preg_match('/^(?=.{1,15}$)[a-zA-Z][a-zA-Z0-9]*(?: [a-zA-Z0-9]+)*$/', $gamertag))
			throw new Exception('The gamertag supplied is invalid!');

		$this->gamertag = $gamertag;

		$curl_opt_array = array(
			CURLOPT_URL => 'http://gamercard.xbox.com/' . $region . '/' . rawurlencode($this->gamertag) . '.card',
			CURLOPT_USERAGENT => 'Xbox-Gamercard-Data;',
			CURLOPT_HEADER => false,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_RETURNTRANSFER => true
		);

		$ch = curl_init();
		curl_setopt_array($ch, $curl_opt_array);
		$data = curl_exec($ch);
		curl_close($ch);

		return $this->parse_data($data);
	}
}

$xbox = new XboxGamercard();
$xbox->build_request('JI IB IL A Z IE');
print_r($xbox);