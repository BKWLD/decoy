<?php

namespace Bkwld\Decoy\Layout;

use URL;
use Request;
use Bkwld\Decoy\Routing\Wildcard;

/**
 * Generate default breadcrumbs and provide a store where they can be
 * overridden before rendering.
 */
class Breadcrumbs
{
    /**
     * Store an explicitly passed in breadcrumbs array
     *
     * @var array
     */
    protected $links = [];

    /**
     * Set the breadcrumbs array
     *
     * @param  array $breadcrumbs Key/value pairs of url/title
     * @return void
     */
    public function set($links)
    {
        $this->links = $links;
    }

    /**
     * Return the breadcrumbs array.  If none has been defined, generate
     * autmatically by parsing the URL
     *
     * @return array
     */
    public function get()
    {
        return $this->links;
    }

    /**
     * Step through the URL, creating controller and model objects for relevant
     * segments to populate richer data in the breadcrumbs, automatically
     *
     * @return array
     */
    public function parseURL()
    {
        $breadcrumbs = [];

        // Get the segments
        $path = Request::path();
        $segments = explode('/', $path);

        // Loop through them in blocks of 2: [list, detail]
        $url = $segments[0];
        for ($i=1; $i<count($segments); $i+=2) {

            // If an action URL, you're at the end of the URL
            if (in_array($segments[$i], ['edit'])) {
                break;
            }

            // Figure out the controller given the url partial
            $url .= '/' . $segments[$i];
            $router = new Wildcard($segments[0], 'GET', $url);
            if (!($controller = $router->detectController())) {
                continue;
            }
            $controller = new $controller;

            // Add controller to breadcrumbs
            $breadcrumbs[URL::to($url)] = strip_tags($controller->title(), '<img>');

            // Add a detail if it exists
            if (!isset($segments[$i+1])) {
                break;
            }
            $id = $segments[$i+1];

            // On a "new" page
            if ($id == 'create') {
                $url .= '/' . $id;
                $breadcrumbs[URL::to($url)] = __('decoy::breadcrumbs.new');

            // On an edit page
            } elseif (is_numeric($id)) {
                $url .= '/' . $id;
                $item = $this->find($controller, $id);
                $title = $item->getAdminTitleAttribute();
                $breadcrumbs[URL::to($url.'/edit')] = $title;
            }
        }

        // Return the full list
        return $breadcrumbs;
    }

    /**
     * Lookup a model instance given the controller and id, including any
     * trashed models in case the controller should show trashed models
     *
     * @param  Controller\Base $controller
     * @param  integer $id
     * @return Model
     */
    public function find($controller, $id)
    {
        $model = $controller->model();
        if ($controller->withTrashed()) {
            return $model::withTrashed()->find($id);
        } else {
            return $model::find($id);
        }
    }

    /**
     * Use the top most breadcrumb label as the page title.  If the breadcrumbs
     * are at least 2 deep, use the one two back as the category for the title
     * if we're not on a listing page (listings are even offsets) for instance,
     * this will make a title like "Admins - John Smith | Site Name"
     *
     * @return string
     */
    public function title()
    {
        $titles = array_values($this->links);
        $title = array_pop($titles);
        if (count($this->links) > 1 && count($this->links) % 2 === 0) {
            $title = array_pop($titles).' - '.$title;
        }
        $title = strip_tags($title);

        return $title;
    }

    /**
     * Get the url for a back button given a breadcrumbs array.  Or return false
     * if there is no where to go back to.
     *
     * @return string|void
     */
    public function back()
    {

        // If there aren't enough breadcrumbs for a back URL, report false
        if (count($this->links) < 2) {
            return;
        }

        // Get the URL from two back from the end in the breadcrumbs array
        $urls = array_keys($this->links);

        return $urls[count($urls) - 2];
    }

    /**
     * If hitting back from a child detail page, goes to the parent detail page
     * rather than to the child listing page.  For instance, if you are editing
     * the slides of a news page, when you go "back", it's back to the news page
     * and not the listing of the news slides
     *
     * @return string
     */
    public function smartBack()
    {

        // If we are on a listing page (an odd length), do the normal stuff
        // http://stackoverflow.com/a/9153969/59160
        if (count($this->links) & 1) {
            return $this->back();
        }

        // If we're on the first level detail page, do normal stuff
        if (count($this->links) === 2) {
            return $this->back();
        }

        // Otherwise, skip the previous (the listing) and go direct to the previous detail
        $urls = array_keys($this->links);

        return $urls[count($urls) - 3];
    }
}
