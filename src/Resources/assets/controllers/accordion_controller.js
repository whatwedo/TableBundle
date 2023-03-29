import { Controller } from '@hotwired/stimulus';
import * as StickyThead from 'stickythead'

export default class extends Controller {
    static targets = ['content', 'arrow']

    toggle(e) {
        const current = e.currentTarget;
        const arrow = current.querySelector('[data-whatwedo--table-bundle--accordion-target=arrow]');
        const isOpen = current.dataset.ariaExpanded == 'true';
        const nextSiblings = this.nextUntil(current, '[data-action="click->whatwedo--table-bundle--accordion#toggle"]');

        if(isOpen) {
            arrow.classList.remove('rotate-90');
            current.dataset.ariaExpanded = 'false';

            nextSiblings.forEach((sibling) => {
                sibling.classList.add('hidden');
            });
        } else {
            arrow.classList.add('rotate-90');
            current.dataset.ariaExpanded = 'true';

            nextSiblings.forEach((sibling) => {
                sibling.classList.remove('hidden');
            });
        }
    }

    /*!
     * Get all following siblings of each element up to but not including the element matched by the selector
     * (c) 2017 Chris Ferdinandi, MIT License, https://gomakethings.com
     * @param  {Node}   elem     The element
     * @param  {String} selector The selector to stop at
     * @param  {String} filter   The selector to match siblings against [optional]
     * @return {Array}           The siblings
     */
    nextUntil(elem, selector, filter) {

        // Setup siblings array
        var siblings = [];

        // Get the next sibling element
        elem = elem.nextElementSibling;

        // As long as a sibling exists
        while (elem) {

            // If we've reached our match, bail
            if (elem.matches(selector)) break;

            // If filtering by a selector, check if the sibling matches
            if (filter && !elem.matches(filter)) {
                elem = elem.nextElementSibling;
                continue;
            }

            // Otherwise, push it to the siblings array
            siblings.push(elem);

            // Get the next sibling element
            elem = elem.nextElementSibling;

        }

        return siblings;

    };
}

