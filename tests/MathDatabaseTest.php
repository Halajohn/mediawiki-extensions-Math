<?php
/**
 * Test the database access and core functionality of MathRenderer.
*
* @group Math
* @group Database //Used by needsDB
*/
class MathDatabaseTest extends MediaWikiTestCase {
	var $renderer;
	const SOME_TEX = "a+b";
	const SOME_HTML = "a<sub>b</sub> and so on";
	const SOME_MATHML = "iℏ∂_tΨ=H^Ψ<mrow><\ci>";
	const SOME_CONSERVATIVENESS = 2;
	const SOME_OUTPUTHASH = 'C65c884f742c8591808a121a828bc09f8<';
	const NUM_BASIC_FIELDS = 5;



	/**
	 * creates a new database connection and a new math renderer
	 * TODO: Check if there is a way to get database access without creating
	 * the connection to the database explicitly
	 * function addDBData() {
	 * 	$this->tablesUsed[] = 'math';
	 * }
	 * was not sufficient.
	 */
	protected function setup() {
		parent::setUp();
		// TODO: figure out why this is necessary
		$this->db = wfGetDB( DB_MASTER );
		// Create a new instance of MathSource
		$this->renderer = new MathTexvc( self::SOME_TEX );
		$this->tablesUsed[] = 'math';
		self::setupTestDB( $this->db, "mathtest" );
}
	/**
	 * Checks the tex and hash functions
	 * @covers MathRenderer::getInputHash()
	 */
	public function testInputHash() {
		$expectedhash = $this->db->encodeBlob( pack( "H32", md5( self::SOME_TEX ) ) );
		$this->assertEquals( $expectedhash, $this->renderer->getInputHash() );
	}

	/**
	 * Helper function to set the current state of the sample renderer instance to the test values
	 */
	public function setValues() {
		// set some values
		$this->renderer->setTex( self::SOME_TEX );
		$this->renderer->setMathml( self::SOME_MATHML );
		$this->renderer->setHtml( self::SOME_HTML );
	}
	/**
	 * Checks database access. Writes an entry and reads it back.
	 * @covers MathRenderer::writeDatabaseEntry()
	 * @covers MathRenderer::readDatabaseEntry()
	 */
	public function testDBBasics() {
		$this->setValues();
		$this->renderer->writeToDatabase( $this->db );
		$renderer2 = new MathTexvc( self::SOME_TEX );
		$this->assertTrue( $renderer2->readFromDatabase(), 'Reading from database failed' );
		// comparing the class object does now work due to null values etc.
		$this->assertEquals( $this->renderer->getTex(), $renderer2->getTex(), "test if tex is the same" );
		$this->assertEquals( $this->renderer->getMathml(), $renderer2->getMathml(), "Check MathML encoding" );
		$this->assertEquals( $this->renderer->getHtml(), $renderer2->getHtml(), 'test if HTML is the same' );
	}



	/**
	 * Checks the creation of the math table without debugging enabled.
	 * @covers MathHooks::onLoadExtensionSchemaUpdates
	 */
	public function testCreateTable() {
		$this->setMwGlobals( 'wgMathValidModes', array( MW_MATH_PNG ) );
		$this->db->dropTable( "math", __METHOD__ );
		$dbu = DatabaseUpdater::newForDB( $this->db );
		$dbu->doUpdates( array( "extensions" ) );
		$this->expectOutputRegex( '/(.*)Creating math table(.*)/' );
		$this->setValues();
		$this->renderer->writeToDatabase();
		$res = $this->db->select( "math", "*" );
		$row = $res->fetchRow();
		$this->assertEquals( count( $row ), 2 * self::NUM_BASIC_FIELDS );
	}
}
