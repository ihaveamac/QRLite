<?php

namespace MediaWiki\Extension\QRLite;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Exception;
use MediaWiki\Html\Html;

/**
 * The actual QRLite Functions
 *
 * They can be used in a programmatic way through this class
 * Use the static getId function as the main entry point
 *
 * @file
 * @ingroup Extensions
 */
class QRLiteFunctions {

	/**
	 * @param array $params
	 * @return string HTML for display of the QR code.
	 */
	public static function generateQRCode( $params = [] ) {
		// Dependency check.
		if ( !class_exists( Builder::class ) ) {
			return Html::errorBox( 'QRLite error: Builder class not found, you may need to run "composer install".' );
		}

		// Defaults and escaping
		$content = self::paramGet( $params, 'prefix', '___MAIN___' );

		$format = self::paramGet( $params, 'format', 'png' );

		$size = self::paramGet( $params, 'size', 6 );
		$margin = self::paramGet( $params, 'margin', 0 );

		$ecc = (int)self::paramGet( $params, 'ecc', 2 );

		// TODO: Doesn't seem to work
		$eccLevel = ErrorCorrectionLevel::Medium;
		if ( $ecc === 1 ) {
			$eccLevel = ErrorCorrectionLevel::Low;
		} else {
			if ( $ecc === 2 ) {
				$eccLevel = ErrorCorrectionLevel::Medium;
			} else {
				if ( $ecc === 3 ) {
					$eccLevel = ErrorCorrectionLevel::Quartile;
				} else {
					if ( $ecc === 4 ) {
						$eccLevel = ErrorCorrectionLevel::High;
					}
				}
			}
		}

		$image = '';
		try {
			$writer = $format === 'svg' ? new SvgWriter() : new PngWriter();
			$writerOptions = [
				SvgWriter::WRITER_OPTION_EXCLUDE_XML_DECLARATION => true
			];
			$qrCode = ( new Builder(
				writer: $writer,
				writerOptions: $writerOptions,
				data: $content,
				encoding: new Encoding( 'UTF-8' ),
				size: $size * 30,
				margin: $margin,
				errorCorrectionLevel: $eccLevel,
			) )->build();

			if ( $format === 'svg' ) {
				$image = '<span class="svg-container" title="' . $content . '">' . $qrCode->getString() . '</span>';
			} else {
				$image = Html::element( 'img', [ 'src' => $qrCode->getDataUri(), 'title' => $content ] );
			}
		} catch ( Exception $e ) {
			$image = '<span class="error-message">' . $e->getMessage() . '</span>';

		}

		$downloadButtons = '';
		$result = '<span class="qrlite-result">' . $image . $downloadButtons . '</span>';

		return $result;
	}

	//////////////////////////////////////////
	// HELPER FUNCTIONS                     //
	//////////////////////////////////////////

	/**
	 * Helper function, that safely checks whether an array key exists
	 * and returns the trimmed value. If it doesn't exist, returns $default or null
	 *
	 * @param array $params
	 * @param string $key
	 * @param mixed|null $default
	 *
	 * @return mixed
	 */
	public static function paramGet( $params, $key, $default = null ) {
		if ( isset( $params[$key] ) ) {
			return trim( $params[$key] );
		} else {
			return $default;
		}
	}

	/**
	 * Debug function that converts an object/array to a <pre> wrapped pretty printed JSON string
	 *
	 * @param mixed $obj
	 * @return string
	 */
	public static function toJSON( $obj ) {
		header( 'Content-Type: application/json' );
		echo json_encode( $obj, JSON_PRETTY_PRINT );
		die();
	}

}
