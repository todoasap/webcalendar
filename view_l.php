<?php
/*
 * $Id$
 *
 * Page Description:
 * This page will display the month "view" with all users's events
 * on the same calendar.  (The other month "view" displays each user
 * calendar in a separate column, side-by-side.)  This view gives you
 * the same effect as enabling layers, but with layers you can only
 * have one configuration of users.
 *
 * Input Parameters:
 * id (*) - specify view id in webcal_view table
 * date - specify the starting date of the view.
 *   If not specified, current date will be used.
 * friendly - if set to 1, then page does not include links or
 *   trailer navigation.
 * (*) required field
 *
 * Security:
 * Must have "allow view others" enabled ($ALLOW_VIEW_OTHER) in
 *   System Settings unless the user is an admin user ($is_admin).
 * If the view is not global, the user must be owner of the view.
 * If the view is global, then and user_sees_only_his_groups is
 * enabled, then we remove users not in this user's groups
 * (except for nonuser calendars... which we allow regardless of group).
 */

include_once 'includes/init.php';
include_once 'includes/views.php';

$error = '';

view_init ( $id );

$INC = array('js/popups.php');
print_header($INC);

set_today($date);

$next = mktime ( 3, 0, 0, $thismonth + 1, 1, $thisyear );
$nextyear = date ( 'Y', $next );
$nextmonth = date ( 'm', $next );
$nextdate = sprintf ( "%04d%02d01", $nextyear, $nextmonth );
$nextYmd = date ( 'Ymd', $next );

$prev = mktime ( 3, 0, 0, $thismonth - 1, 1, $thisyear );
$prevyear = date ( 'Y', $prev );
$prevmonth = date ( 'm', $prev );
$prevdate = sprintf ( "%04d%02d01", $prevyear, $prevmonth );
$prevYmd = date ( 'Ymd', $prev );


if ( ! empty ( $BOLD_DAYS_IN_YEAR ) && $BOLD_DAYS_IN_YEAR == 'Y' ) {
  $boldDays = true;
  $startdate = mktime ( 0, 0, 0, $thismonth -1, 1, $thisyear );
  $enddate = mktime ( 0, 0, 0, $thismonth + 2, 0 , $thisyear );
} else {
  $boldDays = false;
  $startdate = mktime ( 0, 0, 0, $thismonth, 1, $thisyear );
  $enddate = mktime ( 0, 0, 0, $thismonth + 1, 0, $thisyear );
}


$thisdate = date ( 'Ymd', $startdate );

// get users in this view
$viewusers = view_get_user_list ( $id );
if ( count ( $viewusers ) == 0 ) {
  // This could happen if user_sees_only_his_groups  = Y and
  // this user is not a member of any  group assigned to this view
  $error = translate ( 'No users for this view' );
}

if ( ! empty ( $error ) ) {
  echo '<h2>' . translate ( 'Error' ) .
    "</h2>\n" . $error;
  print_trailer ();
  exit;
}

$e_save = array ();
$re_save = array ();
$cnt = count ( $viewusers );
for ( $i = 0; $i < $cnt; $i++ ) {
  /* Pre-Load the repeated events for quckier access */
  $repeated_events = read_repeated_events ( $viewusers[$i], '', $startdate ); 
  $re_save = array_merge($re_save, $repeated_events);
  /* Pre-load the non-repeating events for quicker access */
  $events = read_events ( $viewusers[$i], $startdate, $enddate );
  $e_save = array_merge($e_save, $events);
} 
$events = array ();
$repeated_events = array ();
$ev_save_cnt = count ( $e_save );
$rv_save_cnt = count ( $re_save );
$event_cnt = count ( $events );
$rep_cnt = count ( $repeated_events );
for ( $i = 0; $i < $ev_save_cnt; $i++ ) {
  $should_add = 1;
  for ( $j = 0; $j < $event_cnt && $should_add; $j++ ) {
    if ( ! $e_save[$i]->getClone() && 
      $e_save[$i]->getID() == $events[$j]->getID() ) {
      $should_add = 0;
    }
  }
  if ( $should_add ) {
    array_push ( $events, $e_save[$i] );
  }
}

for ( $i = 0; $i < $rv_save_cnt; $i++ ) {
  $should_add = 1;
  for ( $j = 0; $j < $rep_cnt && $should_add; $j++ ) {
    if ( ! $re_save[$i]->getClone() && 
      $re_save[$i]->getID() == $repeated_events[$j]->getID() ) {
      $should_add = 0;
    }
  }
  if ( $should_add ) {
    array_push ( $repeated_events, $re_save[$i] );
  }
}

display_small_month ( $prevmonth, $prevyear, true, true, "prevmonth", 
  "view_l.php?id=$id&amp;" );
display_small_month ( $nextmonth, $nextyear, true, true, "nextmonth", 
  "view_l.php?id=$id&amp;" );
?>

<div class="title">
<?php display_navigation( 'view_l', false ); ?>

<span class="viewname"><br /><?php echo htmlspecialchars ( $view_name ); ?></span></div>
<br /><br />
<?php
display_month ( $thismonth, $thisyear );
echo "<br />";

if ( ! empty ( $eventinfo ) ) {
  echo $eventinfo;
}

display_unapproved_events ( ( $is_assistant || 
  $is_nonuser_admin ? $user : $login ) );


echo generate_printer_friendly ( 'view_l.php');

print_trailer ();?>
</body>
</html>
