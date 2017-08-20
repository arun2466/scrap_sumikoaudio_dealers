<?php
	error_reporting(E_ALL);
	ini_set('display_errors', 1);

	require_once 'phpQuery-onefile.php';

	function getHtml( $url ){
		$timeout = 10;
    $ch = curl_init($url); // initialize curl with given url
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]); // set  useragent
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // write the response to a variable
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // follow redirects if any
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); // max. seconds to execute
    curl_setopt($ch, CURLOPT_FAILONERROR, 1); // stop when it encounters an error
    $html = @curl_exec($ch);
    return $html;
	}
	function getDealers( $urlInfo ){
		$return = [];

		$url = $urlInfo['url'];
		$u_state = $urlInfo['state'];
		$html = getHtml( $url );
		phpQuery::newDocumentHTML($html);
		
		if( sizeof(pq('ul.deaRis')->find('li')) > 0 ){
			foreach( pq('ul.deaRis')->find('li') as $div ) {
				$brand = "PRO-JECT";
				$state = $u_state;
				$name = $address = $phone = $email = $website = "";

				//name
				if( sizeof( pq($div)->find('p.deaTitInfo')) > 0 ){
					$name = pq($div)->find('p.deaTitInfo')->text();
					$name = trim( $name );
				}				

				//other info
				if( sizeof( pq($div)->find('p.deaDesInfo')) > 0 ){
					$info = pq($div)->find('p.deaDesInfo')->html();
					$myArray = preg_split('/<br[^>]*>/i', $info);

					//phone number
					if( sizeof($myArray) >0 ){
						foreach( $myArray as $p ){
							if (strpos($p, 'Phone') !== false) {
								$explode = explode(':', $p);
								if( sizeof( $explode) >1 ){
									$phone = trim( $explode[1] );
								}
							}
							//collect address from here
							if ( strpos($p, 'Phone') === false &&  strpos($p, 'E-Mail') === false && strpos($p, 'Web') === false ) {
								$address = $address.' '.trim($p);
							}
						}
					}
					//email
					if( sizeof( pq($div)->find('a')) > 0 ){
						foreach( pq($div)->find("a") as $a){
							$check_email = pq($a)->attr('href');
							if( strpos( $check_email, 'mailto:') !== false ){
								$email = pq($a)->text();
							}
						}
					}
					//website
					if( sizeof( pq($div)->find("a[target='_blank']")) > 0 ){
						$website = pq($div)->find("a[target='_blank']")->text();
					}
				}

				$row = array(
					'brand' => $brand,
					'state' => $state,
					'name' => $name,
					'address' => $address,
					'phone' => $phone,
					'email' => $email,
					'website' => $website

				);
				$return[] = $row;
			}
		}
		return $return;
		
	}

	$states = array(
		//'Alabama','Arizona','Arkansas','CA','California','Colorado','Connecticut','Delaware','Florida','Georgia',
		//'Idaho','Illinois','Indiana','Iowa','Kansas','Kentucky','Louisiana','Maine','Maryland','Massachusetts',
		//'Michigan','Minnesota','Missouri','Montana','NC','Nebraska','Nevada','New Hampshire','New Jersey',
		//'New Mexico','New York','North Carolina','North Dakota','Ohio','Oklahoma','Oregon','Pennsylvania','Puerto Rico',
		'South Carolina','South Dakota','Tennessee','Texas','Utah','Virginia','Washington','WI','Wisconsin','Wyoming'
	);

	$urls = array();

	$ALL_DEALERS = array();

	foreach( $states as $state ){
		$state = trim( $state );
		$state1 = str_replace(" " , "+", $state );
		//echo $state;
		for( $i = 1; $i <= 4; $i++ ){
			$urlInfo = array(
				'url' => "http://www.sumikoaudio.net/Dealers/BindDealerListForState?page=$i&brand=-1340377481&stateSelected=$state1",
				'state' => $state
			);

			//echo '<pre>';
			//print_r($urlInfo );

			//break;

			//$dealers = array();
			$dealers = getDealers( $urlInfo );

			//echo "Dealers :: ".sizeof( $dealers ).'<br>';
			$ALL_DEALERS = array_merge($ALL_DEALERS, $dealers);

			//echo "All Dealers :: ".sizeof( $ALL_DEALERS ).'<br>';

			//echo '<pre>';
			//print_r($ALL_DEALERS );

			//die;


//			echo ""


			//$ALL_DEALERS = $dealers; 
			break; // this is for 1st page only
			//die;
			
			//echo $url.'<br><br>';	
		}

		if( $i == 1 ){
			//break;
		}
		//break;
		
	}
	//die;

	//csv

	if( sizeof( $ALL_DEALERS) > 0 ){
			$fileName = 'dealers_list.csv';

			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header('Content-Description: File Transfer');
			header("Content-type: text/csv");
			header("Content-Disposition: attachment; filename={$fileName}");
			header("Expires: 0");
			header("Pragma: public");

			$fh = @fopen( 'php://output', 'w' );

			$headerDisplayed = false;

			foreach ( $ALL_DEALERS as $data ) {
			    // Add a header row if it hasn't been added yet
			    if ( !$headerDisplayed ) {
			        // Use the keys from $data as the titles
			        fputcsv($fh, array_keys($data));
			        $headerDisplayed = true;
			    }
			 
			    // Put the data into the stream
			    fputcsv($fh, $data);
			}
			// Close the file
			fclose($fh);
			// Make sure nothing else is sent, our file is done
			exit;

		}


?>

