<?php

namespace Tests\MobileFrontend\Context;

use MediaWikiTestCase;
use MobileContext;

/**
 * This suite of tests cases tests the behaviour of
 * `MobileContext#shouldDisplayMobileView` and `#shoudDisplayMobileViewInternal`
 * with no stubbed dependencies.
 *
 * @group integration
 */
class MobileContextShouldDisplayMobileViewIntegrationTest extends MediaWikiTestCase {

	/**
	 * @var MobileContext
	 */
	private $context;

	protected function setUp() {
		parent::setUp();

		MobileContext::resetInstanceForTesting();
		$this->context = MobileContext::singleton();
	}

	/**
	 * @covers MobileContext::shouldDisplayMobileView
	 */
	public function test_it_can_be_overridden() {
		$this->context->setForceMobileView( true );

		$this->assertTrue( $this->context->shouldDisplayMobileView() );
	}

	/**
	 * @dataProvider shouldDisplayMobileViewProvider
	 * @covers MobileContext::shouldDisplayMobileView
	 */
	public function testShouldDisplayMobileView(
		$expected,
		$customHeader,
		$format,
		$formatCookie = null,
		$stopMobileRedirectCookie = null,
		$isMobileUA = false
	) {
		$this->setMwGlobals( [
			'wgMFAutodetectMobileView' => true,
			'wgMFMobileHeader' => 'X-Subdomain',
			'wgMobileUrlTemplate' => '%h0.m.%h1.%h2',
		] );

		$request = $this->context->getRequest();

		if ( $customHeader !== null ) {
			$request->setHeader( 'X-Subdomain', $customHeader );
		}

		if ( $format !== null ) {
			$this->context->setUseFormat( $format );
		}

		if ( $formatCookie !== null ) {

			// N.B. that the format and the "stop mobile redirect" cookies
			// ("mf_useformat" and "stopMobileRedirect" respectively) aren't prefix
			// with MediaWiki's cookie prefix ($wgCookiePrefix).
			$request->setCookie( 'mf_useformat', $formatCookie, '' );
		}

		if ( $stopMobileRedirectCookie !== null ) {
			$request->setCookie( 'stopMobileRedirect', $stopMobileRedirectCookie, '' );
		}

		if ( $isMobileUA ) {
			$request->setHeader(
				'User-Agent',

				// An iPhone running iOS 8.0.
				// @codingStandardsIgnoreStart
				'Mozilla/6.0 (iPhone; CPU iPhone OS 8_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/8.0 Mobile/10A5376e Safari/8536.25'
				// @codingStandardsIgnoreEnd
			);
		}

		$this->assertEquals( $expected, $this->context->shouldDisplayMobileView() );
	}

	public function shouldDisplayMobileViewProvider() {
		return [

			// By default, the mobile view shouldn't be displayed.
			[ false, null, null ],

			// When the custom header (by default, "X-Subdomain") is set, then the
			// mobile view should be displayed.
			[ true, 'M', null ],

			// The format (the useformat=<$format> query parameter in the URL)
			// overrides the custom header.
			[ false, 'M', 'desktop' ],

			// If the format is either "mobile-wap" or "mobile", then the mobile view
			// should be displayed.
			[ true, null, 'mobile-wap' ],
			[ true, null, 'mobile' ],
			[ false, null, 'foo' ],

			// If the format cookie ("mf_useformat") is "true", then the mobile view
			// should be displayed.
			[ true, null, null, 'true' ],
			[ false, null, null, 'bar' ],

			// The custom header overrides the "stop mobile redirect" cookie
			// ("stopMobileRedirect").
			[ true, 'M', null, null, 'true' ],

			// When the request is sent from a mobile UA, then the mobile view should
			// be displayed.
			[ true, null, null, null, null, true ],

			// stopMobileRedirect overrides device detection.
			[ false, null, null, null, 'true', true ],
		];
	}
}
