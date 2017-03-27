<?php

namespace Bkwld\Decoy\Markup;

use HtmlObject\Element;

/**
 * An image Element should render nothing if there is no image src data
 */
class ImageElement extends Element
{
    /**
     * Expose a method that lets the $isSelfClosing state be set exeternally
     *
     * @param  boolean $bool
     * @return $this
     */
    public function isSelfClosing($bool = true)
    {
        $this->isSelfClosing = $bool;

        return $this;
    }

    /**
     * Check that an image URL exists before allowing the tag to render
     *
     * @return string
     */
    public function render()
    {
        // Different conditions for different types of tags
        switch ($this->element) {

            // Img tags use the src
            case 'img':
                if (empty($this->getAttribute('src'))) {
                    return '';
                }
                break;

            // Divs have the image as a background-image
            // https://regex101.com/r/eF0oD0/1
            default:
                if (!preg_match('#background-image:\s*url\([\'"]?[\w\/]#',
                    $this->getAttribute('style'))) {
                    return '';
                }
        }

        // Carry on with normal rendering
        return parent::render();
    }
}
