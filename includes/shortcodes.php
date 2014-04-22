<?php

/* Shortcode for displaying the current song
 * Since 2.0.0
 */
function station_shortcode_now_playing($atts) {
	extract( shortcode_atts( array(
			'title' => '',
			'artist' => 1,
			'song' => 1,
			'album' => 0,
			'label' => 0,
			'comments' => 0
	), $atts ) );

	$most_recent = myplaylist_get_now_playing();
	$output = '';

	if($most_recent) {
		$class = '';
		if(isset($most_recent['playlist_entry_new']) && $most_recent['playlist_entry_new'] == 'on') {
			$class = ' class="new"';
		}

		$output .= '<div id="myplaylist-nowplaying"'.$class.'>';
		if($title != '') {
			$output .= '<h3>'.$title.'</h3>';
		}

		if($song == 1) {
			$output .= '<span class="myplaylist-song">'.$most_recent['playlist_entry_song'].'</span> ';
		}
		if($artist == 1) {
			$output .= '<span class="myplaylist-artist">'.$most_recent['playlist_entry_artist'].'</span> ';
		}
		if($album == 1) {
			$output .= '<span class="myplaylist-album">'.$most_recent['playlist_entry_album'].'</span> ';
		}
		if($label == 1) {
			$output .= '<span class="myplaylist-label">'.$most_recent['playlist_entry_label'].'</span> ';
		}
		if($comments == 1) {
			$output .= '<span class="myplaylist-comments">'.$most_recent['playlist_entry_comments'].'</span> ';
		}
		$output .= '<span class="myplaylist-link"><a href="'.$most_recent['playlist_permalink'].'">'.__('View Playlist', 'radio-station').'</a></span> ';
		$output .= '</div>';

	}
	else {
		echo 'No playlists available.';
	}
	return $output;
}
add_shortcode('now-playing', 'station_shortcode_now_playing');


/* Shortcode to fetch all playlists for a given show id
 * Since 2.0.0
 */
function station_shortcode_get_playlists_for_show($atts) {
	extract( shortcode_atts( array(
			'show' => '',
			'limit' => -1
	), $atts ) );

	//don't return anything if we don't have a show
	if($show == '') {
		return false;
	}

	$args = array(
			'numberposts' => $limit,
			'offset' => 0,
			'orderby' => 'post_date',
			'order' => 'DESC',
			'post_type' => 'playlist',
			'post_status' => 'publish',
			'meta_key' => 'playlist_show_id',
			'meta_value' => $show
	);

	$playlists = get_posts($args);

	$output = '';

	$output .= '<div id="myplaylist-playlistlinks">';
	$output .= '<ul class="myplaylist-linklist">';
	foreach($playlists as $playlist) {
		$output .= '<li><a href="';
		$output .= get_permalink($playlist->ID);
		$output .= '">'.$playlist->post_title.'</a></li>';
	}
	$output .= '</ul>';

	$playlist_archive = get_post_type_archive_link('playlist');
	$params = array( 'show_id' => $show );
	$playlist_archive = add_query_arg( $params, $playlist_archive );

	$output .= '<a href="'.$playlist_archive.'">'.__('More Playlists', 'radio-station').'</a>';

	$output .= '</div>';

	return $output;
}
add_shortcode('get-playlists', 'station_shortcode_get_playlists_for_show');

/* Shortcode for displaying a list of all shows
 * Since 2.0.0
 */
function station_shortcode_list_shows($atts) {
	extract( shortcode_atts( array(
			'genre' => ''
	), $atts ) );

	//grab the published shows
	$args = array(
			'numberposts'     => -1,
			'offset'          => 0,
			'orderby'         => 'title',
			'order'           => 'ASC',
			'post_type'       => 'show',
			'post_status'     => 'publish',
			'meta_query' => array(
					array(
							'key' => 'show_active',
							'value' => 'on',
					)
			)
	);

	if($genre != '') {
		$args['genres'] = $genre;
	}

	$shows = get_posts($args);

	//if there are no shows saved, return nothing
	if(!$shows) {
		return false;
	}

	$output = '';

	$output .= '<div id="station-show-list">';
	$output .= '<ul>';
	foreach($shows as $show) {
		$output .= '<li>';
		$output .= '<a href="'.get_permalink($show->ID).'">'.get_the_title($show->ID).'</a>';
		$output .= '</li>';
	}
	$output .= '</ul>';
	$output .= '</div>';
	return $output;
}
add_shortcode('list-shows', 'station_shortcode_list_shows');

/* Shortcode function for current DJ on-air
 * Since 2.0.0
 */
function station_shortcode_dj_on_air($atts) {
	extract( shortcode_atts( array(
			'title' => '',
			'show_avatar' => 0,
			'show_link' => 0,
			'default_name' => '',
			'time' => '12',
			'show_sched' => 1,
			'show_playlist' => 1,
			'show_all_sched' => 0
	), $atts ) );

	//find out which DJ(s) are currently scheduled to be on-air and display them
	$djs = dj_get_current();
	$playlist = myplaylist_get_now_playing();

	$dj_str = '';

	$dj_str .= '<div class="on-air-embedded">';
	if($title != '') {
		$dj_str .= '<h3>'.$title.'</h3>';
	}
	$dj_str .= '<ul class="on-air-list">';

	//echo the show/dj currently on-air
	if($djs['type'] == 'override') {

		$dj_str .= '<li class="on-air-dj">';
		$dj_str .= $djs['all'][0]['title'];
			
		//display the override's schedule if requested
		if($show_sched) {

			if($time == 12) {
				$dj_str .= '<span class="on-air-dj-sched">'.$djs['all'][0]['sched']['start_hour'].':'.$djs['all'][0]['sched']['start_min'].' '.$djs['all'][0]['sched']['start_meridian'].'-'.$djs['all'][0]['sched']['end_hour'].':'.$djs['all'][0]['sched']['end_min'].' '.$djs['all'][0]['sched']['end_meridian'].'</span><br />';
			}
			else {
				$djs['all'][0]['sched'] = station_convert_schedule_to_24hour($djs['all'][0]['sched']);

				$dj_str .= '<span class="on-air-dj-sched">'.$djs['all'][0]['sched']['start_hour'].':'.$djs['all'][0]['sched']['start_min'].' '.'-'.$djs['all'][0]['sched']['end_hour'].':'.$djs['all'][0]['sched']['end_min'].'</span><br />';
			}

			$dj_str .= '</li>';
		}
	}
	else {
		if(isset($djs['all']) && count($djs['all']) > 0) {
			foreach($djs['all'] as $dj) {
				$dj_str .= '<li class="on-air-dj">';
				if($show_avatar) {
					$dj_str .= '<span class="on-air-dj-avatar">'.get_the_post_thumbnail($dj->ID, 'thumbnail').'</span>';
				}

				if($show_link) {
					$dj_str .= '<a href="';
					$dj_str .= get_permalink($dj->ID);
					$dj_str .= '">';
					$dj_str .= $dj->post_title.'</a>';
				}
				else {
					$dj_str .= $dj->post_title;
				}

				if($show_playlist) {
					$dj_str .= '<span class="on-air-dj-playlist"><a href="'.$playlist['playlist_permalink'].'">'.__('View Playlist', 'radio-station').'</a></span>';
				}

				$dj_str .= '<span class="radio-clear"></span>';

				if($show_sched) {
					$scheds = get_post_meta($dj->ID, 'show_sched', true);
					if(!$show_all_sched) { //if we only want the schedule that's relevant now to display...
						$current_sched = station_current_schedule($scheds);
							
						if($current_sched) {
							if($time == 12) {
								$dj_str .= '<span class="on-air-dj-sched">'.__($current_sched['day'], 'radio-station').', '.$current_sched['start_hour'].':'.$current_sched['start_min'].' '.$current_sched['start_meridian'].'-'.$current_sched['end_hour'].':'.$current_sched['end_min'].' '.$current_sched['end_meridian'].'</span><br />';
							}
							else {
								$current_sched = station_convert_schedule_to_24hour($current_sched);
					
								$dj_str .= '<span class="on-air-dj-sched">'.__($current_sched['day'], 'radio-station').', '.$current_sched['start_hour'].':'.$current_sched['start_min'].' '.'-'.$current_sched['end_hour'].':'.$current_sched['end_min'].'</span><br />';
							}
						}
							
					}
					else {
						
						foreach($scheds as $sched) {
							if($time == 12) {
								$dj_str .= '<span class="on-air-dj-sched">'.__($sched['day'], 'radio-station').', '.$sched['start_hour'].':'.$sched['start_min'].' '.$sched['start_meridian'].'-'.$sched['end_hour'].':'.$sched['end_min'].' '.$sched['end_meridian'].'</span><br />';
							}
							else {
								$sched = station_convert_schedule_to_24hour($sched);
									
								$dj_str .= '<span class="on-air-dj-sched">'.__($sched['day'], 'radio-station').', '.$sched['start_hour'].':'.$sched['start_min'].' '.'-'.$sched['end_hour'].':'.$sched['end_min'].'</span><br />';
							}
						}
					}
				}

				$dj_str .= '</li>';
			}
		}
		else {
			$dj_str .= '<li class="on-air-dj default-dj">'.$default_name.'</li>';
		}
	}

	$dj_str .= '</ul>';
	$dj_str .= '</div>';

	return $dj_str;

}
add_shortcode( 'dj-widget', 'station_shortcode_dj_on_air');

/* Shortcode for displaying upcoming DJs/shows
 * Since 2.0.6
*/
function station_shortcode_coming_up($atts) {
	extract( shortcode_atts( array(
			'title' => '',
			'show_avatar' => 0,
			'show_link' => 0,
			'limit' => 1,
			'time' => '12',
			'show_sched' => 1
	), $atts ) );

	//find out which DJ(s) are coming up today
	$djs = dj_get_next($limit);

	$dj_str = '';

	$dj_str .= '<div class="on-air-embedded">';
	if($title != '') {
		$dj_str .= '<h3>'.$title.'</h3>';
	}
	$dj_str .= '<ul class="on-air-list">';

	//echo the show/dj currently on-air
	if(isset($djs['all']) && count($djs['all']) > 0) {
		foreach($djs['all'] as $dj) {
				
			if(is_array($dj) && $dj['type'] == 'override') {
				echo '<li class="on-air-dj">';
				echo $dj['title'];
				if($show_sched) {

					if($time == 12) {
						$dj_str .= '<span class="on-air-dj-sched">'.$dj['sched']['start_hour'].':'.$dj['sched']['start_min'].' '.$dj['sched']['start_meridian'].'-'.$dj['sched']['end_hour'].':'.$dj['sched']['end_min'].' '.$dj['sched']['end_meridian'].'</span><br />';
					}
					else {
						$dj['sched'] = station_convert_schedule_to_24hour($dj['sched']);
							
						$dj_str .= '<span class="on-air-dj-sched">'.$dj['sched']['start_hour'].':'.$dj['sched']['start_min'].' '.'-'.$dj['sched']['end_hour'].':'.$dj['sched']['end_min'].'</span><br />';
							
					}
				}
				echo '</li>';
			}
			else {
				//print_r($dj);
				$dj_str .= '<li class="on-air-dj">';
				if($show_avatar) {
					$dj_str .= '<span class="on-air-dj-avatar">'.get_the_post_thumbnail($dj->ID, 'thumbnail').'</span>';
				}

				if($show_link) {
					$dj_str .= '<a href="';
					$dj_str .= get_permalink($dj->ID);
					$dj_str .= '">';
					$dj_str .= $dj->post_title.'</a>';
				}
				else {
					$dj_str .= $dj->post_title;
				}

				$dj_str .= '<span class="radio-clear"></span>';
				
				if($show_sched) {
					$scheds = get_post_meta($dj->ID, 'show_sched', true);

					
					$curDay = date('l', strtotime(current_time("mysql")));
					$curHour = date('G', strtotime(current_time("mysql")));
					$tomorrowDay = date( "l", (strtotime($curDate) + 86400) );
					 
					$found = 0;
					foreach($scheds as $sched) {
						if($found == 0) { //we only want to display one future schedule
					
							//check if the shift is for the current day or for tomorrow.  If it's not, skip it
							if($sched['day'] != $curDay  && $sched['day'] != $tomorrowDay) {
								continue;
							}
							 
							$convert = station_convert_time($sched);
							if($sched['day'] == $curDay && $convert['start_hour'] <= $curHour) {
								continue;
							}
							 
							if($time == 12) {
								$dj_str .= '<span class="on-air-dj-sched">'.__($sched['day'], 'radio-station').', '.$sched['start_hour'].':'.$sched['start_min'].' '.$sched['start_meridian'].'-'.$sched['end_hour'].':'.$sched['end_min'].' '.$sched['end_meridian'].'</span><br />';
							}
							else {
									
								$sched = station_convert_schedule_to_24hour($sched);
								 
								$dj_str .= '<span class="on-air-dj-sched">'.__($sched['day'], 'radio-station').', '.$sched['start_hour'].':'.$sched['start_min'].' '.'-'.$sched['end_hour'].':'.$sched['end_min'].'</span><br />';
							}
							 
							$found = 1;
						}
					}
					
				}
					
				$dj_str .= '</li>';
			}
		}
	}
	else {
		$dj_str .= '<li class="on-air-dj default-dj">'.__('None Upcoming', 'radio-station').'</li>';
	}

	$dj_str .= '</ul>';
	$dj_str .= '</div>';

	return $dj_str;

}
add_shortcode( 'dj-coming-up-widget', 'station_shortcode_coming_up');
?>