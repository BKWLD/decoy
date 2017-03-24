<?php
namespace Tests\Integration;

use Decoy;
use Tests\TestCase;
use Bkwld\Decoy\Models\Element;

class ElementsTest extends TestCase
{

    /**
     * Test that the elements page loads
     *
     * @return void
     */
    public function testElementsListing()
    {
        $this->auth();

        $response = $this->get('admin/elements');

        $this->assertResponseOk();
    }

    /**
     * Test that elements read from the default yaml file
     *
     * @return void
     */
    public function testElementsShowDefault()
    {
        $this->auth();

        $default = 'Welcome to Decoy';
        $element = Decoy::el('homepage.marquee.title');

        $this->assertEquals($default, $element);
    }

    /**
     * Test that the text field saves properly
     *
     * @return void
     */
    public function testTextElementSave()
    {
        $this->auth();

        $response = $this->post('admin/elements', [
            'homepage|marquee|title' => 'Test'
        ]);

        $element = Decoy::el('homepage.marquee.title');
        $this->assertResponseStatus(302);
        $this->assertEquals('Test', $element);
    }

    /**
     * Test that the text field will populate from database without saving
     *
     * @return void
     */
    public function testTextElementReadsValueFromDatabase()
    {
        $this->auth();

        $create_element = factory(Element::class)->create();
        $database_value = Element::first()->value();

        $element = Decoy::el('homepage.marquee.title')->value();

        $this->assertEquals($database_value, $element);
    }

    /**
     * Test that the text field only saves to the database if a value
     * other than the default is used
     *
     * @return void
     */
    public function testTextElementDoesntSaveUnchanged()
    {
        $this->auth();

        // Make sure first there are no elements in the database
        $first_check = Element::first();
        $this->assertEmpty($first_check);

        // Check to verify the default element is being pulled
        $default_text = 'Welcome to Decoy';
        $default_element = Decoy::el('homepage.marquee.title');
        $this->assertEquals($default_text, $default_element);

        // Make a post request without changing the title
        $response = $this->post('admin/elements', []);
        $this->assertResponseStatus(302);
        $this->assertEmpty(Element::first());
        $this->assertEquals($default_text, $default_element);
    }

}
