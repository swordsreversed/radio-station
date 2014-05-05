<?php
/*
 * Master Show schedule rewrite beta
 * Author: D.Black
 * @Since: 0.1
 */

//jQuery is needed by the output of this code, so let's make sure we have it available
function master_scripts() {
  wp_enqueue_script( 'jquery' );
  wp_enqueue_script('jquery-ui-datepicker');
  wp_enqueue_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
}
add_action( 'init', 'master_scripts' );

//shortcode to display a full schedule of DJs and shows
function master_schedule($atts) {
  global $wpdb;

  extract( shortcode_atts( array(
      'time' => '12',
      'show_link' => 1,
      'display_show_time' => 1,
      'list' => 0
  ), $atts ) );

  $timeformat = $time;

  //$overrides = master_get_overrides(true);

  //set up the structure of the master schedule
  $default_dj = get_option('dj_default_name');

  //check to see what day of the week we need to start on
  $start_of_week = get_option('start_of_week');
  $days_of_the_week = [	'Sunday' => [],
	      'Monday' => [],
	      'Tuesday' => [],
	      'Wednesday' => [],
	      'Thursday' => [],
	      'Friday' => [],
	      'Saturday' => []
	    ];

  $week_start = array_slice($days_of_the_week, $start_of_week);

  foreach($days_of_the_week as $i => $weekday) {
    if($start_of_week > 0) {
      $add = $days_of_the_week[$i];
      unset($days_of_the_week[$i]);

      $days_of_the_week[$i] = $add;
    }
    $start_of_week--;
  }

  $show_shifts = $wpdb->get_results("SELECT `meta`.`post_id`, `meta`.`meta_value` FROM ".$wpdb->prefix."postmeta AS `meta`
		    JOIN ".$wpdb->prefix."postmeta AS `active` ON `meta`.`post_id` = `active`.`post_id`
		    JOIN ".$wpdb->prefix."posts as `posts` ON `posts`.`ID` = `meta`.`post_id`
			  WHERE `meta`.`meta_key` = 'show_sched'
			  AND `posts`.`post_status` = 'publish'
			  AND ( `active`.`meta_key` = 'show_active'
			  AND `active`.`meta_value` = 'on');");


  foreach($show_shifts as $shift) {
    $shift->meta_value = unserialize($shift->meta_value);

    //if a show is not scheduled yet, unserialize will return false... fix that.
    if(!is_array($shift->meta_value)) {
      $shift->meta_value = [];
    }

    foreach ($days_of_the_week as $day => $dayArr) {
      foreach($shift->meta_value as $show) {
	if ($show['day'] == $day) {
	  $show['post_id'] = $shift->post_id;
	  array_push($days_of_the_week[$day], $show);
	}
      }

    }
  }

  //var_dump($days_of_the_week);
  $start_day_time = 6;
  $end_day_time = $start_day_time+24;
  echo '<div class="guide">';
  // first row loop - days
  echo '<div class="guide__column first">';
  echo '<div class="guide__days first">&nbsp;</div>';
  for ($i=$start_day_time; $i < $end_day_time; $i++) {
    // this is nasty. first 12 hrs else - 12 (its over 24) else if the new number j is now over 12 (after midnight) reduce it by 12 again. this could be done functionally much much cleverly.
    if ($i > 12) {
      $j = $i-12;
      if ($j > 12) {
	$k = $j-12;

	echo '<div class="guide__cell">'.$k.'am</div>';
      } else {
	echo '<div class="guide__cell">'.$j.'pm</div>';
      }

    } elseif ($i == 12) {
      echo '<div class="guide__cell">'.$i.'pm</div>';
    } else {
      echo '<div class="guide__cell">'.$i.'am</div>';
    }

  }
  echo '</div>';

    foreach ($days_of_the_week as $dayKey => $day) {
      echo '<div class="guide__column">';
      echo '<div class="guide__days"><strong>'.$dayKey.'</strong></div>';

      // loop through each hour - check for matching show
      for ($i=$start_day_time; $i < $end_day_time; $i++) {

	$sf=0;
	foreach ($day as $key => $show) {
	  $meridian_hr = $i.':00';

	  $show_start = $show['start_hour'].$show['start_meridian'];
	  $show_end = $show['end_hour'].$show['end_meridian'];

	  $block_time_formatted = date ("G:i", strtotime($meridian_hr));
	  $show_start_formatted = date("G:i", strtotime($show_start));
	  $show_end_formatted = date("G:i", strtotime($show_end));

	  // check if shows starts or continues in this hour
	  if ($show_start_formatted == $block_time_formatted) {
	    $show_length = $show_end_formatted-$show_start_formatted;
	    echo '<div class="guide__cell x'.$show_length.'">';

	    echo $meridian_hr.' '.$block_time_formatted.' '.get_the_title($show['post_id']);
	    //echo get_the_title($show['post_id']).'<br>'.$show_start_formatted;
	    echo '</div>';
	    $sf=1;
	    break;

	  } elseif (($block_time_formatted > $show_start_formatted) && ($block_time_formatted < $show_end_formatted)) {
	    // in a show - do nothing
	    $sf=1;
	    break;
	  }
	}

	if ($sf==0) {
	  // no show - empty div
	  echo '<div class="guide__cell x1">&nbsp;</div>';
	}

      }
      echo '</div>';
    }

  echo '</div>'; //end guide container
  echo '<div style="clear:both;">&nbsp;</div>';


} // end master schedule
add_shortcode( 'master-schedule', 'master_schedule');


function master_fetch_js_filter(){
  $js = '<div id="master-genre-list"><span class="heading">'.__('Genres', 'radio-station').': </span>';

  $taxes = get_terms('genres', array('hide_empty' => true, 'orderby' => 'name', 'order' => 'ASC'));
  foreach($taxes as $i => $tax) {
    $js .= '<a href="javascript:show_highlight(\''.$tax->name.'\')">'.$tax->name.'</a>';
    if($i < count($taxes)) {
      $js .= ' | ';
    }
  }

  $js .= '</div>';

  $js .= '<script type="text/javascript">';
  $js .= 'function show_highlight(myclass) {'; // hardcoded styles yuk!
  $js .= '	jQuery(".master-show-entry").css("border","1px solid white");';
  $js .= '	jQuery("." + myclass).css("border","3px solid red");';
  $js .= '}';
  $js .= '</script>';

  return $js;
}

?>

