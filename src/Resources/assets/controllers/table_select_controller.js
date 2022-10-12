import {Controller} from '@hotwired/stimulus';
import 'regenerator-runtime/runtime'

export default class extends Controller {

    static targets = ["ids", "selector", "checkAll", "unCheckAll", "selectedCount"]
    static values = {
        footSelectedTemplate: String
    }

    connect() {
        if (!this.hasIdsTarget) {
            return;
        }
        this.idsTarget.value = '[]';
        this.selectorTargets.forEach(selector => {
            selector.checked = false;
        });
    }

    selectId(event) {
        if (!event.target.dataset.entityId) {
            return;
        }
        const eventId = parseInt(event.target.dataset.entityId);
        const ids = this.getIds();

        if (ids.includes(eventId)) {
            this.removeId(eventId);
            return;
        }

        this.addId(eventId);
    }

    tableTargetChanged() {
        this.syncSelectedIds();
        this.updateSelectedCount();
    }

    checkAll() {
        this.selectorTargets.forEach(selector => {
            if (selector.checked) {
                return;
            }
            this.addId(parseInt(selector.dataset.entityId));
            selector.checked = true;
        });
        this.checkAllTarget.classList.add('hidden');
        this.unCheckAllTarget.classList.remove('hidden');
    }

    unCheckAll() {
        this.selectorTargets.forEach(selector => {
            this.removeId(parseInt(selector.dataset.entityId));
            selector.checked = false;
        });
        this.checkAllTarget.classList.remove('hidden');
        this.unCheckAllTarget.classList.add('hidden');
    }

    hasId(id) {
        let ids = this.getIds();
        return ids.includes(id)
    }

    addId(id) {
        let ids = this.getIds();
        ids.push(id);
        this.idsTarget.value = JSON.stringify(ids);
        this.updateSelectedCount();
    }

    removeId(id) {
        let ids = this.getIds();
        ids = ids.filter(function (value, index, arr) {
            return value != id;
        })
        this.idsTarget.value = JSON.stringify(ids);
        this.updateSelectedCount();
        if (ids.length == 0) {
            this.checkAllTarget.classList.remove('hidden');
            this.unCheckAllTarget.classList.add('hidden');
        }
    }

    updateSelectedCount() {
        const count = this.getIds().length;

        if (count === 0) {
            this.selectedCountTarget.classList.add('hidden');
            return;
        }

        this.selectedCountTarget.classList.remove('hidden');
        this.selectedCountTarget.innerHTML = this.footSelectedTemplateValue.replace('{count}', count);
    }

    syncSelectedIds() {
        let ids = this.getIds();
        this.selectorTargets.forEach(selector => {
            selector.checked = ids.includes(parseInt(selector.dataset.entityId));
        });

    }

    async doAction(event) {
        event.preventDefault();

        let ids = this.getIds();
        if (ids.length == 0) {
            alert('no selection');
            return;
        }
        let formData = new FormData;
        ids.forEach(id => {
            formData.append('ids[]', id);
        });
        await fetch(event.target.getAttribute('href'), {
            method: 'POST',
            body: formData
        }) .then(async response => {
            if (response.status == 200) {
                response.json().then(data => {
                    window.location.href = data.url;
                });
            } else {
                alert('error. please try again');
            }
        });
    }

    getIds() {
        return JSON.parse(this.idsTarget.value);
    }
}
