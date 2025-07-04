<?php
/*
HLstatsX Community Edition - Real-time player and clan rankings and statistics
Copyleft (L) 2008-20XX Nicholas Hastings (nshastings@gmail.com)
http://www.hlxcommunity.com

HLstatsX Community Edition is a continuation of 
ELstatsNEO - Real-time player and clan rankings and statistics
Copyleft (L) 2008-20XX Malte Bayer (steam@neo-soft.org)
http://ovrsized.neo-soft.org/

ELstatsNEO is an very improved & enhanced - so called Ultra-Humongus Edition of HLstatsX
HLstatsX - Real-time player and clan rankings and statistics for Half-Life 2
http://www.hlstatsx.com/
Copyright (C) 2005-2007 Tobias Oetzel (Tobi@hlstatsx.com)

HLstatsX is an enhanced version of HLstats made by Simon Garner
HLstats - Real-time player and clan rankings and statistics for Half-Life
http://sourceforge.net/projects/hlstats/
Copyright (C) 2001  Simon Garner
            
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

For support and installation notes visit http://www.hlxcommunity.com
*/

function printMap($type = 'main')
{
	global $db, $game, $g_options, $clandata, $clan;
	
	if ($type == 'main') {
		// Use async loading and a callback function
		echo ('<script src="https://maps.googleapis.com/maps/api/js?key=' . GOOGLE_MAPS_API_KEY . '&loading=async&callback=initMap&libraries=marker" defer></script>');
	}
?> 
		<script type="text/javascript">
		/* <![CDATA[ */
		
		// Global map variable
		var map; 
		var point_icon = "<?php echo IMAGE_PATH; ?>/mm_20_blue.png";
		var point_icon_red = "<?php echo IMAGE_PATH; ?>/mm_20_red.png";
		var shadow_icon = "<?php echo IMAGE_PATH; ?>/mm_20_shadow.png"; // Assuming shadow exists

		// Preload images (can be kept if needed elsewhere, but not strictly necessary for map markers now)
		function preloadImages() {
			var d=document; if(d.images){ if(!d.p) d.p=new Array();
			var i,j=d.p.length,a=preloadImages.arguments; for(i=0; i<a.length; i++)
			if (a[i].indexOf("#")!=0){ d.p[j]=new Image; d.p[j++].src=a[i];}}
		}
		<?php echo "preloadImages(point_icon, ".(($type == 'main')?"point_icon_red, ":'')."shadow_icon);"; ?>

		// Map initialization function (callback)
		async function initMap() {
			// Import necessary libraries after API loads
			const { Map } = await google.maps.importLibrary("maps");
  			const { AdvancedMarkerElement } = await google.maps.importLibrary("marker"); // Import marker library here if not in script tag

<?php
			// this create mapLatLng and mapZoom
			printMapCenter(($type == 'clan' && $clandata['mapregion'] != '') ? $clandata['mapregion'] : $g_options['google_map_region']);
			// this creates mapType
			printMapType($g_options['google_map_type']);
?>
			var myOptions = {
				center: mapLatLng,
				mapTypeId: mapType,
				zoom: mapZoom, 
				scrollwheel: false,
				mapTypeControl: true,
				mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU},
				navigationControl: true,
				navigationControlOptions: {style: google.maps.NavigationControlStyle.ZOOM_PAN},
				mapId: 'HLSTATS_MAP' // Recommended for Advanced Markers
			};

			map = new Map(document.getElementById("map"), myOptions); // Use imported Map

			// --- Marker Creation Functions (Updated) ---
			function createMarker(point, city, country, player_info) {
				var html_text = '<table class="gmapstab"><tr><td colspan="2" class="gmapstabtitle" style="border-bottom:1px solid black;">'+city+', '+country+'</td></tr>';
				for ( i=0; i<player_info.length; i++) {
					html_text += '<tr><td><a href="hlstats.php?mode=playerinfo&player='+player_info[i][0]+'">'+player_info[i][1]+'</a></td></tr>';
					html_text += '<tr><td>Kills/Deaths</td><td>'+player_info[i][2]+':'+player_info[i][3]+'</td></tr>';
<?php
					if ($type == 'main') {
						echo "html_text += '<tr><td>Time</td><td>'+player_info[i][4]+'</td></tr>';";
					}
?>
				}
				html_text += '</table>';
				
				const infowindow = new google.maps.InfoWindow({
					content: html_text,
					ariaLabel: city + ', ' + country // Accessibility improvement
				});

				// Create DOM element for the marker icon
				const markerIcon = document.createElement('img');
				markerIcon.src = point_icon;
				markerIcon.style.cursor = 'pointer'; // Indicate it's clickable
				markerIcon.title = city + ', ' + country; // Tooltip on hover

				const marker = new AdvancedMarkerElement({ // Use AdvancedMarkerElement
					position: point,
					map: map,
					content: markerIcon, // Set the DOM element as content
					title: city + ', ' + country // Redundant with icon title, but good practice
				});

				// Add listener to the icon element, not the marker itself
				markerIcon.addEventListener("click", () => {
					infowindow.open({
						anchor: marker, // Anchor InfoWindow to the marker
						map: map
					});
				});
			}

<?php
			if ($type == 'main') {
?>
			function createMarkerS(point, servers, city, country, kills) {
				var html_text =   '<table class="gmapstab"><tr><td colspan="2" class="gmapstabtitle" style="border-bottom:1px solid black;">'+city+', '+country+'</td></tr>';
				for ( i=0; i<servers.length; i++) {
					html_text += '<tr><td><a href=\"hlstats.php?mode=servers&server_id=' + servers[i][0] + '&game=<?php echo $game; ?>\">' + servers[i][2] + '</a></td></tr>';
					html_text += '<tr><td>' + servers[i][1] + ' (<a href=\"steam://connect/' + servers[i][1] + '\">connect</a>)</td></tr>';
				}
				html_text += '<tr><td>'+kills+' kills</td></tr></table>';
				
				const infowindow = new google.maps.InfoWindow({
					content: html_text,
					ariaLabel: city + ', ' + country // Accessibility improvement
				});

				// Create DOM element for the marker icon
				const markerIcon = document.createElement('img');
				markerIcon.src = point_icon_red;
				markerIcon.style.cursor = 'pointer'; // Indicate it's clickable
				markerIcon.title = city + ', ' + country + ' (' + kills + ' kills)'; // Tooltip on hover

				const marker = new AdvancedMarkerElement({ // Use AdvancedMarkerElement
					position: point,
					map: map,
					content: markerIcon, // Set the DOM element as content
					title: city + ', ' + country // Redundant with icon title, but good practice
				});

				// Add listener to the icon element, not the marker itself
				markerIcon.addEventListener("click", () => {
					infowindow.open({
						anchor: marker, // Anchor InfoWindow to the marker
						map: map
					});
				});
			}
	<?php
			// --- Data Fetching and Marker Placement ---
				$db->query("SELECT serverId, IF(publicaddress != '', publicaddress, CONCAT(address, ':', port)) AS addr, name, kills, lat, lng, city, country FROM hlstats_Servers WHERE game='$game' AND lat IS NOT NULL AND lng IS NOT NULL");

				$servers = array();
				while ($row = $db->fetch_array())
				{
					if (!isset($servers[$row['lat'] . ',' . $row['lng']]))
					{
						$servers[$row['lat'] . ',' . $row['lng']] = array('lat' => $row['lat'], 'lng' => $row['lng'], 'addr' => $row['addr'], 'city' => $row['city'], 'country' => $row['country']);
					}
					$servers[$row['lat'] . ',' . $row['lng']]['servers'][] = array('serverId' => $row['serverId'], 'addr' => $row['addr'], 'name' => $row['name'], 'kills' => $row['kills']);
				}
				foreach ($servers as $map_location)
				{
					$kills = 0;
					$servers_js = array();
					foreach ($map_location['servers'] as $server)
					{
						$search_pattern = array("/[^A-Za-z0-9\[\]*.,=()!\"$%&^`ґ':;ЯІі#+~_\-|<>\/@{}дцьДЦЬ ]/");
						$replace_pattern = array("");
						$server['name'] = preg_replace($search_pattern, $replace_pattern, $server['name']);
						$temp = "[" . $server['serverId'] . ',';
						$temp .= "'" . htmlspecialchars(urldecode(preg_replace($search_pattern, $replace_pattern, $server['addr'])), ENT_QUOTES) . '\',';
						$temp .= "'" . htmlspecialchars(urldecode(preg_replace($search_pattern, $replace_pattern, $server['name'])), ENT_QUOTES) . '\']';
						$servers_js[] = $temp;
						$kills += $server['kills'];
					}
					// Call marker function inside initMap
					echo 'createMarkerS(new google.maps.LatLng(' . $map_location['lat'] . ', ' . $map_location['lng'] . '), [' . implode(',', $servers_js) . '], "' . htmlspecialchars(urldecode($map_location['city']), ENT_QUOTES) . '", "' . htmlspecialchars(urldecode($map_location['country']), ENT_QUOTES) . '", ' . $kills . ");\n";
				}

				$data = array();
				$db->query("SELECT 
							hlstats_Livestats.* 
						FROM 
							hlstats_Livestats
						INNER JOIN    
							hlstats_Servers 
							ON (hlstats_Servers.serverId=hlstats_Livestats.server_id)
						WHERE 
							hlstats_Livestats.cli_lat IS NOT NULL 
							AND hlstats_Livestats.cli_lng IS NOT NULL
							AND hlstats_Servers.game='$game'
							");
				$players = array();
				while ($row = $db->fetch_array())
				{
					if (!isset($players[$row['cli_lat'] . ',' . $row['cli_lng']]))
					{
						$players[$row['cli_lat'] . ',' . $row['cli_lng']] = array('cli_lat' => $row['cli_lat'], 'cli_lng' => $row['cli_lng'], 'cli_city' => $row['cli_city'], 'cli_country' => $row['cli_country']);
					}
					$search_pattern = array("/[^A-Za-z0-9\[\]*.,=()!\"$%&^`ґ':;ЯІі#+~_\-|<>\/@{}дцьДЦЬ ]/");
					$replace_pattern = array("");
					$row['name'] = preg_replace($search_pattern, $replace_pattern, $row['name']);
					$players[$row['cli_lat'] . ',' . $row['cli_lng']]['players'][] = array('playerId' => $row['player_id'], 'name' => $row['name'], 'kills' => $row['kills'], 'deaths' => $row['deaths'], 'connected' => $row['connected']);
				}

				foreach ($players as $map_location)
				{
					$kills = 0;
					$players_js = array();
					foreach ($map_location['players'] as $player)
					{
						$stamp = time() - $player['connected'];
						$hours = sprintf("%02d", floor($stamp / 3600));
						$min = sprintf("%02d", floor(($stamp % 3600) / 60));
						$sec = sprintf("%02d", floor($stamp % 60));
						$time_str = $hours . ":" . $min . ":" . $sec;
						$temp = "[" . $player['playerId'] . ',';
						$temp .= "'" . htmlspecialchars(urldecode(preg_replace($search_pattern, $replace_pattern, $player['name'])), ENT_QUOTES) . "',";
						$temp .= $player['kills'] . ',';
						$temp .= $player['deaths'] . ',';
						$temp .= "'" . $time_str . "']";
						$players_js[] = $temp;
					}
					// Call marker function inside initMap
					echo "createMarker(new google.maps.LatLng(" . $map_location['cli_lat'] . ", " . $map_location['cli_lng'] . "), \"" . htmlspecialchars(urldecode($map_location['cli_city']), ENT_QUOTES) . "\", \"" . htmlspecialchars(urldecode($map_location['cli_country']), ENT_QUOTES) . '", [' . implode(',', $players_js) . "]);\n";
				}
			} else if ($type == 'clan') {
				$db->query("
					SELECT
						playerId, lastName, country, skill, kills, deaths, lat, lng, city, country
					FROM hlstats_Players
					WHERE clan=$clan AND hlstats_Players.hideranking = 0
					GROUP BY hlstats_Players.playerId
				");
				$players = array();
				while ( $row = $db->fetch_array() )
				{
					if ( !isset($players[ $row['lat'] . ',' . $row['lng'] ]) )
					{
						$players[ $row['lat'] . ',' . $row['lng'] ] = array(
							'lat' => $row['lat'], 'lng' => $row['lng'], 'city' => $row['city'], 'country' => $row['country']
						);
					}
					$search_pattern = array("/[^A-Za-z0-9\[\]*.,=()!\"$%&^`ґ':;ЯІі#+~_\-|<>\/@{}дцьДЦЬ ]/");
					$replace_pattern = array("");
					$row['lastName'] = preg_replace($search_pattern, $replace_pattern, $row['lastName']);
					$players[ $row['lat'] . ',' . $row['lng'] ]['players'][] = array(
						'playerId' => $row['playerId'], 'name' => $row['lastName'], 'kills' => $row['kills'], 'deaths' => $row['deaths']
					);
				}
				
				foreach ( $players as $location )
				{
					$kills = 0;
					$players_js = array();
					foreach ( $location['players'] as $player )
					{
						$temp = "[" .  $player['playerId'] . ',';
						$temp .= "'" . htmlspecialchars(urldecode(preg_replace($search_pattern, $replace_pattern, $player['name'])), ENT_QUOTES) . "',";
						$temp .= $player['kills'] . ',';
						$temp .= $player['deaths'] . ']';
						$players_js[] = $temp;
					}
					// Call marker function inside initMap
					echo "createMarker(new google.maps.LatLng(" . $location['lat'] . ", " . $location['lng'] . "), \"" . htmlspecialchars(urldecode($location['city']), ENT_QUOTES) . "\", \"" . htmlspecialchars(urldecode($location['country']), ENT_QUOTES) . "\", [" . implode(",", $players_js) . "]);\n";
				}
			}
?>
		} // End of initMap function

		/* ]]> */
	</script>
<?php
}

function printMapCenter($country)
{
	switch (strtoupper($country))
	{
		case 'EUROPE':
			echo "var mapLatLng = new google.maps.LatLng(48.8, 8.5);\nvar mapZoom = 3;";
			break;
		case 'NORTH AMERICA':
			echo "var mapLatLng = new google.maps.LatLng(45.0, -97.0);\nvar mapZoom = 3;";
			break;
		case 'SOUTH AMERICA':
			echo "var mapLatLng = new google.maps.LatLng(-14.8, -61.2);\nvar mapZoom = 3;";
			break;
		case 'NORTH AFRICA':
			echo "var mapLatLng = new google.maps.LatLng(25.4, 8.4);\nvar mapZoom = 4;";
			break;
		case 'SOUTH AFRICA':
			echo "var mapLatLng = new google.maps.LatLng(-29.0, 23.7);\nvar mapZoom = 5;";
			break;
		case 'NORTH EUROPE':
			echo "var mapLatLng = new google.maps.LatLng(62.6, 15.4);\nvar mapZoom = 4;";
			break;
		case 'EAST EUROPE':
			echo "var mapLatLng = new google.maps.LatLng(51.9, 31.8);\nvar mapZoom = 4;";
			break;
		case 'GERMANY':
			echo "var mapLatLng = new google.maps.LatLng(51.1, 10.1);\nvar mapZoom = 5;";
			break;
		case 'FRANCE':
			echo "var mapLatLng = new google.maps.LatLng(47.2, 2.4);\nvar mapZoom = 5;";
			break;
		case 'SPAIN':
			echo "var mapLatLng = new google.maps.LatLng(40.3, -4.0);\nvar mapZoom = 5;";
			break;
		case 'UNITED KINGDOM':
			echo "var mapLatLng = new google.maps.LatLng(54.0, -4.3);\nvar mapZoom = 5;";
			break;
		case 'DENMARK':
			echo "var mapLatLng = new google.maps.LatLng(56.1, 9.2);\nvar mapZoom = 6;";
			break;
		case 'SWEDEN':
			echo "var mapLatLng = new google.maps.LatLng(63.2, 16.3);\nvar mapZoom = 4;";
			break;
		case 'NORWAY':
			echo "var mapLatLng = new google.maps.LatLng(65.6, 13.1);\nvar mapZoom = 4;";
			break;
		case 'FINLAND':
			echo "var mapLatLng = new google.maps.LatLng(65.1, 26.6);\nvar mapZoom = 4;";
			break;
		case 'NETHERLANDS':
			echo "var mapLatLng = new google.maps.LatLng(52.3, 5.4);\nvar mapZoom = 7;";
			break;
		case 'BELGIUM':
			echo "var mapLatLng = new google.maps.LatLng(50.7, 4.5);\nvar mapZoom = 7;";
			break;
		case 'SUISSE':
			echo "var mapLatLng = new google.maps.LatLng(46.8, 8.2);\nvar mapZoom = 7;";
			break;
		case 'AUSTRIA':
			echo "var mapLatLng = new google.maps.LatLng(47.7, 14.1);\nvar mapZoom = 7;";
			break;
		case 'POLAND':
			echo "var mapLatLng = new google.maps.LatLng(52.1, 19.3);\nvar mapZoom = 6;";
			break;
		case 'ITALY':
			echo "var mapLatLng = new google.maps.LatLng(42.6, 12.7);\nvar mapZoom = 5;";
			break;
		case 'TURKEY':
			echo "var mapLatLng = new google.maps.LatLng(39.0, 34.9);\nvar mapZoom = 6;";
			break;
		case 'ROMANIA':
			echo "var mapLatLng = new google.maps.LatLng(45.94, 24.96);\nvar mapZoom = 6;";
			break;
		case 'BRAZIL':
			echo "var mapLatLng = new google.maps.LatLng(-12.0, -53.1);\nvar mapZoom = 4;";
			break;
		case 'ARGENTINA':
			echo "var mapLatLng = new google.maps.LatLng(-34.3, -65.7);\nvar mapZoom = 3;";
			break;
		case 'RUSSIA':
			echo "var mapLatLng = new google.maps.LatLng(65.7, 98.8);\nvar mapZoom = 3;";
			break;
		case 'ASIA':
			echo "var mapLatLng = new google.maps.LatLng(20.4, 95.6);\nvar mapZoom = 3;";
			break;
		case 'CHINA':
			echo "var mapLatLng = new google.maps.LatLng(36.2, 104.0);\nvar mapZoom = 4;";
			break;
		case 'JAPAN':
			echo "var mapLatLng = new google.maps.LatLng(36.2, 136.8);\nvar mapZoom = 5;";
			break;
		case 'SOUTH KOREA':
			echo "var mapLatLng = new google.maps.LatLng(36.6, 127.8);\nvar mapZoom = 6;";
			break;
		case 'TAIWAN':
			echo "var mapLatLng = new google.maps.LatLng(23.6, 121);\nvar mapZoom = 7;";
			break;	
		case 'AUSTRALIA':
			echo "var mapLatLng = new google.maps.LatLng(-26.1, 134.8);\nvar mapZoom = 4;";
			break;
		case 'CANADA':
			echo "var mapLatLng = new google.maps.LatLng(60.0, -97.0);\nvar mapZoom = 3;";
			break;
		case 'WORLD':
			echo "var mapLatLng = new google.maps.LatLng(25.0, 8.5);\nvar mapZoom = 2;";
			break;
		default:
			echo "var mapLatLng = new google.maps.LatLng(48.8, 8.5);\nvar mapZoom = 3;";
			break;
	}
	echo "\n";
}

function printMapType($maptype)
{
	switch (strtoupper($maptype))
	{
		case 'SATELLITE':
			echo 'var mapType = google.maps.MapTypeId.SATELLITE;';
			break;
		case 'MAP':
			echo 'var mapType = google.maps.MapTypeId.ROADMAP;';
			break;
		case 'HYBRID':
			echo 'var mapType = google.maps.MapTypeId.HYBRID;';
			break;
		case 'PHYSICAL':
			echo 'var mapType = google.maps.MapTypeId.TERRAIN;';
			break;
		default:
			break; // Keep default or set to ROADMAP?
	}
	echo "\n";
}
?>