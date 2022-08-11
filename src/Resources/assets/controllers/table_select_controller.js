import {Controller} from '@hotwired/stimulus';

export default class extends Controller {

    static targets = ["ids", "selector", "checkAll", "unCheckAll"]

    connect() {
        this.idsTarget.value = '[]';
        this.selectorTargets.forEach(selector => {
            selector.checked = false;
        });
    }

    selectId(event) {
        if (event.target.dataset.entityId) {
            this.addId(parseInt(event.target.dataset.entityId))
        } else {
            this.removeId(parseInt(event.target.dataset.entityId))
        }
    }

    tableTargetChanged() {
        this.syncSelectedIds();
    }

    checkAll() {
        this.selectorTargets.forEach(selector => {
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
    }

    removeId(id) {
        let ids = this.getIds();
        ids = ids.filter(function (value, index, arr) {
            return value != id;
        })
        this.idsTarget.value = JSON.stringify(ids);
    }


    syncSelectedIds() {
        let ids = this.getIds();
        this.selectorTargets.forEach(selector => {
            if (ids.includes(parseInt(selector.dataset.tableSelectIdParam))) {
                selector.checked = true;
            } else {
                selector.checked = false;
            }
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
        await fetch(event.target.href, {
            method: 'POST',
            body: formData
        }) .then(async response => {
            if (response.status == 200) {
                let data = await response.json();
                if (data.reload) {
                    window.location.reload();
                }
                if (data.redirect) {
                    window.location.href = data.redirect;
                }
                if (!data.reload && !data.redirect) {
                    console.warn('Batch Action Controller should return json data with reload or redirect information');
                }
            }
        })
    }

    getIds() {
        return JSON.parse(this.idsTarget.value);
    }
}
