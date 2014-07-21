<?php

class SYAMailingList_Admin
{
  const FORM_COLLECTION = 'syamladmin'; 

  static $DATE_OPTIONS = array(
	'Today',
	'Yesterday',
	'Last 7 Days',
	'This Month',
	'Last Month',
	'Last 12 Months',
	'All Time'
  );
  
  public $from_date = null;
  public $to_date = null;
  public $country = null;
  public $date_option = 'Today';
  
  protected $syaml = null;
  
  public function __construct( $syaml )
  {
	$this->syaml = $syaml;
  }
  
  public function run()
  {
	$this->init_filters();
	$this->data = $this->get_list( $this->from_date, $this->to_date, $this->country );
	if ( isset( $_REQUEST[ self::FORM_COLLECTION ] ) && isset( $_REQUEST[ self::FORM_COLLECTION ]['export'] ) && count( $this->data ) > 0 ) {
	  $this->export_csv($this->data);
	}
  }

  public function display()
  {
	$this->render_list($this->data);
  }
  
  public function export_csv( $data )
  {
	header( "Content-type: text/csv" );
	header( "Content-Disposition: attachment; filename=emails.csv" );
	header( "Pragma: no-cache" );
	header( "Expires: 0" );
	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array_keys( $data[0] ) );
	foreach ( $data as $row ) {
	  $row['country'] = $this->syaml->country_name_from_id( $row['country'] );
	  fputcsv( $out, $row );
	}
	fclose( $out );
	exit();
  }
  
  public function render_list($data)
  {
	require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . '/adminlist.php';
  }
  
  public function build_where( $from_date, $to_date, $country = null )
  {
	$sql = '';
	$wheres = array();
	if ( $country ) {
	  $wheres[] = SYAMailingList::COUNTRY_FIELD . " = '" . esc_sql( $country ) . "' ";
	}
	$wheres[] = SYAMailingList::DATE_FIELD . " >= '" . esc_sql( $from_date->format( 'Y-m-d' ) ) . "' ";
	$wheres[] = SYAMailingList::DATE_FIELD . " <= '" . esc_sql( $to_date->format( 'Y-m-d 23:59:59' ) ) . "' ";
	
	if ( count( $wheres ) > 0 ) {
	  $sql .= ' WHERE ' . join( ' AND ', $wheres ) . ' ';
	}	
	return $sql;
  }
  
  public function get_count( $from_date, $to_date, $country = null )
  {
	global $wpdb;
	$sql = 'SELECT COUNT(*) FROM ' . $this->syaml->table_name()
			. ' ' . $this->build_where( $from_date, $to_date, $country );
	return $wpdb->get_value( $sql, 0, 0 );
  }
  
  public function get_list( $from_date, $to_date, $country = null )
  {
	global $wpdb;
	
	$sql = 'SELECT ' 
					. SYAMailingList::EMAIL_FIELD . ', '
					. SYAMailingList::NAME_FIELD . ', '
					. SYAMailingList::COUNTRY_FIELD . ', ' 
					. SYAMailingList::DATE_FIELD  
					. ' FROM ' . $this->syaml->table_name();

	$sql .= $this->build_where( $from_date, $to_date, $country );
	
	$sql .= ' ORDER BY ' . SYAMailingList::DATE_FIELD . ' DESC ';
	return $wpdb->get_results( $sql, ARRAY_A );
  }
  
  public function country_name_from_id($id)
  {
	return $this->syaml->country_name_from_id($id);
  }
  
  public function init_filters()
  {
	if ( isset( $_REQUEST[ self::FORM_COLLECTION ] ) ) {
	  $data = $_REQUEST[ self::FORM_COLLECTION ];
	  $this->country = !empty( $data['country']) ? $data['country'] : null;
	  $this->date_option = !empty( $data['date_option'] ) && in_array( $data['date_option'], self::$DATE_OPTIONS ) ? $data['date_option'] : $this->date_option;
	}
	$this->to_date = new DateTime();
	$this->from_date = new DateTime();
	switch ( $this->date_option ) {
	  case 'Yesterday':
		$this->from_date->sub( new DateInterval('P1D') );
		$this->to_date = $this->from_date;
		break;
	  case 'Last 7 Days':
		$this->from_date->sub( new DateInterval('P7D') );
		break;
	  case 'This Month':
		$format = $this->to_date->format('Y-m-01');
		$this->from_date = DateTime::createFromFormat( 'Y-m-d', $format ); 
		break;
	  case 'Last Month':
		$this->to_date = DateTime::createFromFormat( 'Y-m-d', $this->to_date->format('Y-m-1') );
		$this->from_date = DateTime::createFromFormat( 'Y-m-d', $this->to_date->format('Y-m-1') );
		$this->from_date->sub( new DateInterval( 'P1M' ) );
		$this->to_date->sub( new DateInterval( 'P1D' ) );
		break;
	  case 'Last 12 Months':
		$this->from_date->sub( new DateInterval(('P12M') ) );
		break;
	  case 'All Time':
		$this->from_date = DateTime::createFromFormat( 'Y-m-d', '1980-01-01' );
		break;
	  default:
	  case 'Today':
		$this->from_date = new DateTime();
		break;
	}
  }
}