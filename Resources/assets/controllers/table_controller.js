import { Controller } from 'stimulus';
import * as StickyThead from 'stickythead'

export default class extends Controller {
    static targets = ['table']

    connect() {
        StickyThead.apply([this.tableTarget], {
            scrollableArea: this.element
        });
    }
}

