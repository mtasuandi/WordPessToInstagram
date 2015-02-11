<?php 
class Instagram {
	public function post( $username, $password, $imageUrl, $caption ) {
		$agent = $this->generateUserAgent();
		$obj = explode( '/', $imageUrl );
		$fileName = rand() . '_' . end( $obj );
		$downloadImage = $this->downloadImage( $imageUrl, $fileName );

		if ( $downloadImage ) {
			$filePath = get_template_directory() . '/temp/' . $fileName;
			$guid = $this->generateGuid();
			$device_id = 'android-' . $guid;
			$data = '{"device_id":"' . $device_id . '","guid":"' . $guid . '","username":"' . $username . '","password":"' . $password . '","Content-Type":"application/x-www-form-urlencoded; charset=UTF-8"}';
			$sig = $this->generateSignature( $data );
			$data = 'signed_body=' . $sig . '.' . urlencode( $data ) . '&ig_sig_key_version=4';
			$login = $this->sendRequest( 'accounts/login/', true, $data, $agent, false );

			if ( strpos( $login[1], 'Sorry, an error occurred while processing this request.' ) ) {
    		return "Request failed, there's a chance that this proxy/ip is blocked";
			} else {
				if ( empty( $login[1] ) ) {
					return "Empty response received from the server while trying to login";
				} else {
					$obj = @json_decode( $login[1], true );
					if ( empty( $obj ) ) {
						return "Could not decode the response: " . $body;
					} else {
			    	$postData = $this->getPostData( $fileName );
			    	$post = $this->sendRequest( 'media/upload/', true, $postData, $agent, true );
			    	
			    	if ( empty( $post[1] ) ) {
				    	return "Empty response received from the server while trying to post the image";
			    	} else {
			      	$obj = @json_decode( $post[1], true );
				    	
				    	if ( empty( $obj ) ) {
					    	return "Could not decode the response";
				    	} else {
					    	$status = $obj['status'];
					    	
					    	if ( $status == 'ok' ) {
	              	$caption = preg_replace( "/\r|\n/", '', $caption );
						    	$media_id = $obj['media_id'];
						    	$device_id = 'android-' . $guid;
						    	$data = '{"device_id":"' . $device_id . '","guid":"' . $guid . '","media_id":"' . $media_id . '","caption":"' . trim( $caption ) . '","device_timestamp":"' . time() . '","source_type":"5","filter_type":"0","extra":"{}","Content-Type":"application/x-www-form-urlencoded; charset=UTF-8"}';
						    	$sig = $this->generateSignature( $data );
						    	$new_data = 'signed_body=' . $sig . '.' . urlencode( $data ) . '&ig_sig_key_version=4';
									$conf = $this->sendRequest( 'media/configure/', true, $new_data, $agent, true );

						    	if ( empty( $conf[1] ) ) {
										return "Empty response received from the server while trying to configure the image";
						    	} else {
							    	if ( strpos( $conf[1], 'login_required') ) {
								    	return "You are not logged in. There's a chance that the account is banned";
							    	} else {
								    	$obj = @json_decode( $conf[1], true );
								    	$status = $obj['status'];

								    	if ( $status != 'fail' ) {
								    		$this->destroyCookieFile();
								    		$this->destroyInstagramFile( $postData['photo'] );
									    	return 'Success';
								    	} else {
									    	return 'Fail';
								    	}
							    	}
						    	}
					    	} else {
						    	return "Status isn't okay";
					    	}
				    	}
			    	}
		    	}
    		}
			}
		}
	}

	private function destroyCookieFile() {
		if ( file_exists( get_template_directory() . '/temp/cookie.txt' ) ) {
			unlink( get_template_directory() . '/temp/cookie.txt' );
		}
	}

	private function destroyInstagramFile( $filePath ) {
		if ( file_exists( $filePath ) ) {
			unlink( $filePath );
		}
	}

	private function downloadImage( $url, $filename ) {
		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_BINARYTRANSFER,1 );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 0 ); 
		$rawdata = curl_exec( $ch );
		curl_close( $ch );

		if ( $rawdata !== false ) {
			if ( !file_put_contents( get_template_directory() . '/temp/' . $filename, $rawdata ) ) {
				return false;
			}
		} else {
			return false;
		}
		return true;
	}

	private function generateUserAgent() {
		$resolutions = array( '720x1280', '320x480', '480x800', '1024x768', '1280x720', '768x1024', '480x320' );
		$versions = array( 'GT-N7000', 'SM-N9000', 'GT-I9220', 'GT-I9100' );
		$dpis = array( '120', '160', '320', '240' );

		$ver = $versions[array_rand( $versions )];
		$dpi = $dpis[array_rand( $dpis )];
		$res = $resolutions[array_rand( $resolutions )];
		
		return 'Instagram 4.' . mt_rand( 1, 2 ) . '.' . mt_rand( 0, 2 ) . ' Android (' . mt_rand( 10, 11 ) . '/' . mt_rand( 1, 3 ) . '.' . mt_rand( 3, 5 ) . '.' . mt_rand( 0, 5 ) . '; ' . $dpi . '; ' . $res . '; samsung; ' . $ver . '; ' . $ver . '; smdkc210; en_US)';
	}

	private function generateGuid() {
		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x', 
			mt_rand( 0, 65535 ),
			mt_rand( 0, 65535 ),
			mt_rand( 0, 65535 ),
			mt_rand( 16384, 20479 ),
			mt_rand( 32768, 49151 ),
			mt_rand( 0, 65535 ),
			mt_rand( 0, 65535 ),
			mt_rand( 0, 65535 ) );
	}

	private function generateSignature( $data ) {
		return hash_hmac( 'sha256', $data, 'b4a23f5e39b5929e0666ac5de94c89d1618a2916' );
	}

	public function sendRequest( $url, $post, $post_data, $user_agent, $cookies ) {
    $ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, 'https://instagram.com/api/v1/' . $url );
		curl_setopt( $ch, CURLOPT_USERAGENT, $user_agent );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );

		if ( $post ) {
			curl_setopt( $ch, CURLOPT_POST, true );
			if ( isset( $post_data['photo'] ) ) {
				$new_post_data = array();
				$new_post_data['device_timestamp'] = $post_data['device_timestamp'];
				$new_post_data['photo'] = class_exists( 'CurlFile', false ) ? new CURLFile( $post_data['photo'] ) : "@{$post_data['photo']}";
			} else {
				$new_post_data = $post_data;
			}
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $new_post_data );
		}
		
		$cookieFile = get_template_directory() . '/temp/cookie.txt';

		if ( $cookies ) {
			curl_setopt( $ch, CURLOPT_COOKIEFILE, $cookieFile );			
		} else {
			curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookieFile );
		}
			
		$response = curl_exec( $ch );
		$http = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );
			
		return array( $http, $response );
	}

	private function getPostData( $filename ) {
		$post_data = array();
		$path = get_template_directory() . '/temp/';
		$filePath = $path . $filename;
		if ( file_exists( $filePath ) ) {
			$convertImageToJpg = $this->convertImage( $filePath, $path . 'converted_' . $filename );
			if ( $convertImageToJpg ) {
				$this->squareImage( $path . 'converted_' . $filename, $path . 'instagram_' . $filename );
				$post_data = array( 'device_timestamp' => time(), 
					'photo' => $path . 'instagram_' . $filename
				);
				if ( file_exists( $path . $filename ) ) {
					unlink( $path . $filename );	
				}
				
				if ( file_exists( $path . 'converted_' . $filename ) ) {
					unlink( $path . 'converted_' . $filename );
				}
			}
			return $post_data;
		} else {
			return false;
		}
	}

	private function convertImage( $originalImage, $outputImage, $quality = 100 ) {
    $exploded = explode( '.', $originalImage );
    $ext = $exploded[count( $exploded ) - 1]; 

    if ( preg_match( '/jpg|jpeg/i', $ext ) ) {
    	$imageTmp = imagecreatefromjpeg( $originalImage );
    } elseif ( preg_match( '/png/i', $ext ) ) {
    	$imageTmp = imagecreatefrompng( $originalImage );
    } elseif ( preg_match( '/gif/i', $ext ) ) {
    	$imageTmp = imagecreatefromgif( $originalImage );
    } elseif ( preg_match( '/bmp/i', $ext ) ) {
    	$imageTmp = imagecreatefrombmp( $originalImage );
    } else {
    	return 0;
    }

    imagejpeg( $imageTmp, $outputImage, $quality );
	  @imagedestroy( $imageTmp );
		return 1;
	}

	private function squareImage( $imgSrc, $imgDes, $thumbSize = 1000 ) {
    list( $width, $height ) = getimagesize( $imgSrc );

    $myImage = imagecreatefromjpeg( $imgSrc );

    if ( $width > $height ) {
        $y = 0;
        $x = ( $width - $height ) / 2;
        $smallestSide = $height;
    } else {
        $x = 0;
        $y = ( $height - $width ) / 2;
        $smallestSide = $width;
    }

    $thumb = imagecreatetruecolor( $thumbSize, $thumbSize );
    imagecopyresampled( $thumb, $myImage, 0, 0, $x, $y, $thumbSize, $thumbSize, $smallestSide, $smallestSide );

    if ( file_exists( $imgSrc ) ) {
    	unlink( $imgSrc );
    }
    imagejpeg( $thumb, $imgDes, 100 );
    @imagedestroy( $myImage );
    @imagedestroy( $thumb );
	}
}