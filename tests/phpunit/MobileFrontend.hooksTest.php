<?php

/**
 * @group MobileFrontend
 */
class MobileFrontendHooksTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		MobileContext::resetInstanceForTesting();
	}

	/**
	 * Test findTagLine when output has no wikibase elements
	 *
	 * @covers MobileFrontendHooks::findTagline
	 */
	public function testFindTaglineWhenNoElementsPresent() {
		$po = new ParserOutput();
		$fallback = function () {
			$this->fail( 'Fallback shouldn\'t be called' );
		};
		$this->assertEquals( MobileFrontendHooks::findTagline( $po, $fallback ), false );
	}

	/**
	 * Test findTagLine when output has no wikibase elements
	 *
	 * @covers MobileFrontendHooks::findTagline
	 */
	public function testFindTaglineWhenItemIsNotPresent() {
		$poWithDesc = new ParserOutput();
		$poWithDesc->setProperty( 'wikibase-shortdesc', 'desc' );

		$fallback = function () {
			$this->fail( 'Fallback shouldn\'t be called' );
		};
		$this->assertEquals( MobileFrontendHooks::findTagline( $poWithDesc, $fallback ), 'desc' );
	}

	/**
	 * Test findTagLine when output has no wikibase elements
	 *
	 * @covers MobileFrontendHooks::findTagline
	 */
	public function testFindTaglineWhenOnlyItemIsPresent() {
		$fallback = function ( $item ) {
			$this->assertEquals( 'W2', $item );
			return 'Hello Wikidata';
		};

		$poWithItem = new ParserOutput();
		$poWithItem->setProperty( 'wikibase_item', 'W2' );
		$this->assertEquals( MobileFrontendHooks::findTagline( $poWithItem, $fallback ),
			'Hello Wikidata' );
	}

	/**
	 * Test findTagLine when output has no wikibase elements
	 *
	 * @covers MobileFrontendHooks::findTagline
	 */
	public function testFindTaglineWhenWikibaseAttrsArePresent() {
		$fallback = function () {
			$this->fail( 'Fallback shouldn\'t be called' );
		};

		$poWithBoth = new ParserOutput();
		$poWithBoth->setProperty( 'wikibase-shortdesc', 'Hello world' );
		$poWithBoth->setProperty( 'wikibase_item', 'W2' );
		$this->assertEquals( MobileFrontendHooks::findTagline( $poWithBoth, $fallback ),
			'Hello world' );
	}

	/**
	 * Test no alternate/canonical link is set on Special:MobileCite
	 *
	 * @covers MobileFrontendHooks::onBeforePageDisplay
	 */
	public function testSpecialMobileCiteOnBeforePageDisplay() {
		$this->setMwGlobals( [
			'wgMFEnableManifest' => false,
			'wgMobileUrlTemplate' => true,
			'wgMFNoindexPages' => true
		] );
		$param = $this->getContextSetup( 'mobile', [], SpecialPage::getTitleFor( 'MobileCite' ) );
		$out = $param['out'];
		$skin = $param['sk'];

		MobileFrontendHooks::onBeforePageDisplay( $out, $skin );

		$links = $out->getLinkTags();
		$this->assertEquals( 0, count( $links ),
			'test, no alternate or canonical link is added' );
	}
	/**
	 * Test headers and alternate/canonical links to be set or not
	 *
	 * @dataProvider onBeforePageDisplayDataProvider
	 * @covers MobileFrontendHooks::onBeforePageDisplay
	 */
	public function testOnBeforePageDisplay( $mobileUrlTemplate, $mfNoindexPages,
		$mfEnableXAnalyticsLogging, $mfAutoDetectMobileView, $mfVaryOnUA, $mfXAnalyticsItems,
		$isAlternateCanonical, $isXAnalytics, $mfVaryHeaderSet
	) {
		// set globals
		$this->setMwGlobals( [
			'wgMFEnableManifest' => false,
			'wgMobileUrlTemplate' => $mobileUrlTemplate,
			'wgMFNoindexPages' => $mfNoindexPages,
			'wgMFEnableXAnalyticsLogging' => $mfEnableXAnalyticsLogging,
			'wgMFAutodetectMobileView' => $mfAutoDetectMobileView,
			'wgMFVaryOnUA' => $mfVaryOnUA,
		] );

		// test with forced mobile view
		$param = $this->getContextSetup( 'mobile', $mfXAnalyticsItems );
		$out = $param['out'];
		$skin = $param['sk'];

		// run the test
		MobileFrontendHooks::onBeforePageDisplay( $out, $skin );

		// test, if alternate or canonical link is added, but not both
		$links = $out->getLinkTags();
		$this->assertEquals( $isAlternateCanonical, count( $links ),
			'test, if alternate or canonical link is added, but not both' );
		// if there should be an alternate or canonical link, check, if it's the correct one
		if ( $isAlternateCanonical ) {
			// should be canonical link, not alternate in mobile view
			$this->assertEquals( 'canonical', $links[0]['rel'],
				'should be canonical link, not alternate in mobile view' );
		}
		$varyHeader = $out->getVaryHeader();
		$this->assertEquals( $mfVaryHeaderSet, strpos( $varyHeader, 'User-Agent' ) !== false,
			'check the status of the User-Agent vary header when wgMFVaryOnUA is enabled' );

		// check, if XAnalytics is set, if it should be
		$resp = $param['context']->getRequest()->response();
		$this->assertEquals( $isXAnalytics, (bool)$resp->getHeader( 'X-Analytics' ),
			'check, if XAnalytics is set, if it should be' );

		// test with forced desktop view
		$param = $this->getContextSetup( 'desktop', $mfXAnalyticsItems );
		$out = $param['out'];
		$skin = $param['sk'];

		// run the test
		MobileFrontendHooks::onBeforePageDisplay( $out, $skin );
		// test, if alternate or canonical link is added, but not both
		$links = $out->getLinkTags();
		$this->assertEquals( $isAlternateCanonical, count( $links ),
			'test, if alternate or canonical link is added, but not both' );
		// if there should be an alternate or canonical link, check, if it's the correct one
		if ( $isAlternateCanonical ) {
			// should be alternate link, not canonical in desktop view
			$this->assertEquals( 'alternate', $links[0]['rel'],
				'should be alternate link, not canonical in desktop view' );
		}
		$varyHeader = $out->getVaryHeader();
		// check, if the vary header is set in desktop mode
		$this->assertEquals( $mfVaryHeaderSet, strpos( $varyHeader, 'User-Agent' ) !== false,
			'check, if the vary header is set in desktop mode' );
		// there should never be an XAnalytics header in desktop mode
		$resp = $param['context']->getRequest()->response();
		$this->assertEquals( false, (bool)$resp->getHeader( 'X-Analytics' ),
			'there should never be an XAnalytics header in desktop mode' );
	}

	/**
	 * Creates a new set of object for the actual test context, including a new
	 * outputpage and skintemplate.
	 *
	 * @param string $mode The mode for the test cases (desktop, mobile)
	 * @param array $mfXAnalyticsItems
	 * @param Title $title
	 * @return array Array of objects, including MobileContext (context),
	 * SkinTemplate (sk) and OutputPage (out)
	 */
	protected function getContextSetup( $mode, $mfXAnalyticsItems, $title = null ) {
		MobileContext::resetInstanceForTesting();
		$context = MobileContext::singleton();

		// create a DerivativeContext to use in MobileContext later
		$mainContext = new DerivativeContext( RequestContext::getMain() );
		// create a new, empty OutputPage
		$out = new OutputPage( $context );
		// create a new, empty SkinTemplate
		$skin = new SkinTemplate();
		if ( is_null( $title ) ) {
			// create a new Title (main page)
			$title = Title::newMainPage();
		}
		// create a FauxRequest to use instead of a WebRequest object (FauxRequest forces
		// the creation of a FauxResponse, which allows to investigate sent header values)
		$request = new FauxRequest();
		// set the new request object to the context
		$mainContext->setRequest( $request );
		// set the main page title to the context
		$mainContext->setTitle( $title );
		// set the context to the SkinTemplate
		$skin->setContext( $mainContext );
		// set the OutputPage to the context
		$mainContext->setOutput( $out );
		// set the DerivativeContext as a base to MobileContext
		$context->setContext( $mainContext );
		// set the mode to MobileContext
		$context->setUseFormat( $mode );
		// if there are any XAnalytics items, add them
		foreach ( $mfXAnalyticsItems as $key => $val ) {
			$context->addAnalyticsLogItem( $key, $val );
		}

		// return the stuff
		return [
			'out' => $out,
			'sk' => $skin,
			'context' => $context,
		];
	}

	/**
	 * Dataprovider for testOnBeforePageDisplay
	 */
	public function onBeforePageDisplayDataProvider() {
		return [
			// wgMobileUrlTemplate, wgMFNoindexPages, wgMFEnableXAnalyticsLogging, wgMFAutodetectMobileView,
			// wgMFVaryOnUA, XanalyticsItems, alternate & canonical link, XAnalytics, Vary header User-Agent
			[ true, true, true, true, true,
				[ 'mf-m' => 'a', 'zero' => '502-13' ], 1, true, false, ],
			[ true, false, true, false, false,
				[ 'mf-m' => 'a', 'zero' => '502-13' ], 0, true, false, ],
			[ false, true, true, true, true,
				[ 'mf-m' => 'a', 'zero' => '502-13' ], 0, true, true, ],
			[ false, false, true, false, false,
				[ 'mf-m' => 'a', 'zero' => '502-13' ], 0, true, false, ],
			[ true, true, false, true, true, [], 1, false, false, ],
			[ true, false, false, false, false, [], 0, false, false, ],
			[ false, true, false, true, true, [], 0, false, true, ],
			[ false, false, false, false, false, [], 0, false, false, ],
			[ false, false, false, false, true, [], 0, false, false, ],
		];
	}

	/**
	 * @covers MobileFrontendHooks::onTitleSquidURLs
	 */
	public function testOnTitleSquidURLs() {
		$this->setMwGlobals( [
			'wgMobileUrlTemplate' => '%h0.m.%h1.%h2',
			'wgServer' => 'http://en.wikipedia.org',
			'wgArticlePath' => '/wiki/$1',
			'wgScriptPath' => '/w',
			'wgScript' => '/w/index.php',
		] );
		$title = Title::newFromText( 'PurgeTest' );

		$urls = $title->getCdnUrls();

		$expected = [
			'http://en.wikipedia.org/wiki/PurgeTest',
			'http://en.wikipedia.org/w/index.php?title=PurgeTest&action=history',
			'http://en.m.wikipedia.org/w/index.php?title=PurgeTest&action=history',
			'http://en.m.wikipedia.org/wiki/PurgeTest',
		];

		$this->assertArrayEquals( $expected, $urls );
	}

	/**
	 * @dataProvider provideOnPageRenderingHash
	 * @covers MobileFrontendHooks::onPageRenderingHash
	 */
	public function testOnPageRenderingHash(
		$shouldConfstrChange,
		$stripResponsiveImages
	) {
		$context = MobileContext::singleton();
		$context->setStripResponsiveImages( $stripResponsiveImages );

		$expectedConfstr = $confstr = '';

		if ( $shouldConfstrChange ) {
			$expectedConfstr = '!responsiveimages=0';
		}

		$user = new User();
		$forOptions = [];

		MobileFrontendHooks::onPageRenderingHash( $confstr, $user, $forOptions );

		$this->assertEquals( $expectedConfstr, $confstr );
	}

	public static function provideOnPageRenderingHash() {
		return [
			[ true, true ],
			[ false, false ],
		];
	}

	/**
	 * @dataProvider provideDoThumbnailBeforeProduceHTML
	 * @covers MobileFrontendHooks::onPageRenderingHash
	 */
	public function testDoThumbnailBeforeProduceHTML(
		$expected,
		$mimeType,
		$stripResponsiveImages = true
	) {
		$file = $mimeType ? $this->factoryFile( $mimeType ) : null;
		$thumbnail = new ThumbnailImage(
			$file,

			// The following is stub data that stops `ThumbnailImage#__construct`,
			// triggering a warning.
			'/foo.svg',
			false,
			[
				'width' => 375,
				'height' => 667
			]
		);

		MobileContext::singleton()->setStripResponsiveImages( $stripResponsiveImages );

		// We're only asserting that the `srcset` attribute is unset.
		$attribs = [ 'srcset' => 'bar' ];

		$linkAttribs = [];

		MobileFrontendHooks::onThumbnailBeforeProduceHTML(
			$thumbnail,
			$attribs,
			$linkAttribs
		);

		$this->assertEquals( $expected, array_key_exists( 'srcset', $attribs ) );
	}

	/**
	 * Creates an instance of `File` which has the given MIME type.
	 *
	 * @return File
	 */
	private function factoryFile( $mimeType ) {
		$file = $this->getMockBuilder( 'File' )
			->disableOriginalConstructor()
			->getMock();

		$file->method( 'getMimeType' )
			->willReturn( $mimeType );

		return $file;
	}

	public static function provideDoThumbnailBeforeProduceHTML() {
		return [
			[ false, 'image/jpg' ],

			// `ThumbnailImage#getFile` can return `null`.
			[ false, null ],

			// It handles an image with a whitelisted MIME type.
			[ true, 'image/svg+xml' ],

			// It handles the stripping of responsive image variants from the parser
			// output being disabled.
			[ true, 'image/jpg', false ],
		];
	}
}
