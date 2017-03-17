<?php

namespace Tests\MobileFrontend\Skins;

use MediaWikiTestCase;
use MobileContext;
use OutputPage;
use SkinMinerva;
use TestingAccessWrapper;
use Title;

class TestSkinMinerva extends SkinMinerva {

	/**
	 * The Minimum Viable Constructor for SkinMinerva.
	 *
	 * @FIXME Why doesn't SkinMinerva have its dependencies injected?
	 *
	 * @param MobileContext $mobileContext
	 */
	public function __construct( MobileContext $mobileContext ) {
		$this->mobileContext = $mobileContext;
	}
}

/**
 * @covers SkinMinerva
 * @group MobileFrontend
 */
class SkinMinervaTest extends MediaWikiTestCase {

	public function testAddToBodyAttributes() {
		// The `class` attribute gets set to the "bodyClassName" property by
		// default.
		$this->assertContains(
			'no-js',
			$this->addToBodyAttributes( 'no-js', false )
		);

		$classes = $this->addToBodyAttributes( 'no-js', true );

		$this->assertContains( 'no-js', $classes );
	}

	private function addToBodyAttributes(
		$bodyClassName
	) {
		$context = MobileContext::singleton();

		$outputPage = $context->getOutput();
		$outputPage->setProperty( 'bodyClassName', $bodyClassName );

		$bodyAttrs = [ 'class' => '' ];

		$this->factorySkin( $context )
			->addToBodyAttributes( $outputPage, $bodyAttrs );

		return explode( ' ', $bodyAttrs[ 'class' ] );
	}

	private function factorySkin( MobileContext $context ) {
		return new TestSkinMinerva( $context );
	}

	/**
	 * @dataProvider provideHasCategoryLinks
	 * @param array $categoryLinks
	 * @param bool $expected
	 */
	public function testHasCategoryLinks( array $categoryLinks, $expected ) {
		$outputPage = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();
		$outputPage->expects( $this->once() )
			->method( 'getCategoryLinks' )
			->will( $this->returnValue( $categoryLinks ) );

		$skin = TestingAccessWrapper::newFromObject(
			$this->getMockBuilder( SkinMinerva::class )
				->disableOriginalConstructor()
				->getMock()
		);
		$skin->expects( $this->once() )
			->method( 'getOutput' )
			->will( $this->returnValue( $outputPage ) );
		$this->assertEquals( $skin->hasCategoryLinks(), $expected );
	}

	public function provideHasCategoryLinks() {
		return [
			[ [], false ],
			[
				[
					'normal' => '<ul><li><a href="/wiki/Category:1">1</a></li></ul>'
				],
				true
			],
			[
				[
					'hidden' => '<ul><li><a href="/wiki/Category:Hidden">Hidden</a></li></ul>'
				],
				true
			],
			[
				[
					'normal' => '<ul><li><a href="/wiki/Category:1">1</a></li></ul>',
					'hidden' => '<ul><li><a href="/wiki/Category:Hidden">Hidden</a></li></ul>'
				],
				true
			],
			[
				[
					'unexpected' => '<ul><li><a href="/wiki/Category:1">1</a></li></ul>'
				],
				false
			],
		];
	}

	/**
	 * Test whether the font changer module is correctly added to the list context modules
	 *
	 * @covers       SkinMinerva::getContextSpecificModules
	 * @dataProvider provideGetContextSpecificModules
	 * @param string $configName Config name that needs to be set
	 * @param mixed $configValue Config value that is assigned to $configName
	 * @param string $moduleName Module name that is being tested
	 * @param bool $expected Whether the module is expected to be returned by the function being tested
	 */
	public function testGetContextSpecificModules( $configName, $configValue,
												   $moduleName, $expected ) {
		$skin = TestingAccessWrapper::newFromObject(
			$this->getMockBuilder( SkinMinerva::class )
				->disableOriginalConstructor()
				->setMethods( [ 'getTitle' ] )
				->getMock()
		);
		$skin->mobileContext = MobileContext::singleton();
		$skin->isMobileMode = $skin->mobileContext->shouldDisplayMobileView();
		$title = Title::newFromText( 'Test' );
		$skin->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$this->setMwGlobals( $configName, [
			'base' => $configValue
		] );

		if ( $expected ) {
			$this->assertContains( $moduleName, $skin->getContextSpecificModules() );
		} else {
			$this->assertNotContains( $moduleName, $skin->getContextSpecificModules() );
		}
	}

	public function provideGetContextSpecificModules() {
		return [
			[ 'wgMinervaEnableFontChanger', true, 'skins.minerva.fontchanger', true ],
			[ 'wgMinervaEnableFontChanger', false, 'skins.minerva.fontchanger', false ],
			[ 'wgMinervaEnableBackToTop', true, 'skins.minerva.backtotop', true ],
			[ 'wgMinervaEnableBackToTop', false, 'skins.minerva.backtotop', false ],
		];
	}
}
