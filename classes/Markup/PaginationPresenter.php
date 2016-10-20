<?php namespace Bkwld\Decoy\Markup;

// Deps
use Illuminate\Pagination\BootstrapThreePresenter;
use Illuminate\Support\HtmlString;

/**
 * Extend the bootstrap presetner to provide more customization options
 */
class PaginationPresenter extends BootstrapThreePresenter {

	/**
	 * Render just first, prev, current, next, and last pages on mobile
	 *
	 * @return Illuminate\Support\HtmlString
	 */
	public function renderMobile() {
		if (!$this->hasPages()) return '';
		return new HtmlString(sprintf(
			'<ul class="pagination">%s %s %s %s %s</ul>',
			$this->getFirstPageLink(),
			$this->getPreviousButton('&lsaquo;'),
			$this->getActivePageWrapper($this->currentPage(), $this->currentPage()),
			$this->getNextButton('&rsaquo;'),
			$this->getLastPageLink()
		));
	}

	/**
	 * Get first page button
	 *
	 * @return string
	 */
	protected function getFirstPageLink() {
		if ($this->currentPage() <= 1) {
			return $this->getDisabledTextWrapper('&laquo;');
		} else {
			return $this->getPageLinkWrapper($this->paginator->url(1), '&laquo;');
		}
	}

	/**
	 * Get last page button
	 *
	 * @return string
	 */
	protected function getLastPageLink() {
		if (! $this->paginator->hasMorePages()) {
			return $this->getDisabledTextWrapper('&raquo;');
		} else {
			return $this->getPageLinkWrapper($this->paginator->url(1), '&raquo;');
		}
	}

	/**
	 * Render the per page selector links
	 *
	 * @param  array $options
	 * @return Illuminate\Support\HtmlString
	 */
	public function renderPerPageOptions($options) {

		// Get the current perpage value
		$selected = request('count', $options[0]);

		// Wrap the links that will be generated in some HTML
		return new HtmlString(sprintf(
			'<ul class="pagination">
				<li class="disabled"><span>Show</span></li>
				%s
			</ul>',

			// Loop through the options and conver to links in the style of the
			// bootstrap pagination
			implode('', array_map(function($option) use ($selected) {

				// Render active state if on selected per page amount
				if ($selected == $option) {
					return $this->getActivePageWrapper(
						ucfirst($option));

				// Non-selected link state
				} else {
					return $this->getAvailablePageWrapper(
						$this->paginator->addQuery('count', $option)->url(1),
						ucfirst($option));
				}
			}, $options))
		));
	}

}
