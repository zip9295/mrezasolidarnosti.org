import CrudPage from "https://skeletor.greenfriends.systems/skeletorjs/src/Page/CrudPage.js";
import Loader from "https://skeletor.greenfriends.systems/skeletorjs/src/Loader/Loader.js";

export default class Educator extends CrudPage {
    #data;
    #formTabs;
    #formAction;
    constructor() {
        super();
        this.dataTableOptions = {
            enableCheckboxes: true,
            shiftCheckboxModifier: true
        };
        this.modalOptions = {
            createModalWidth: '70%',
            createModalHeight: '70%',
            editModalWidth: '70%',
            editModalHeight: '70%'
        }
    }

    actionFilter = (action, entity) => {
        const role = document.getElementById('navigation').dataset.role;
        if (action.getName() === 'delete' && role != 1) {
            return false;
        }
        return action;
    }

    tdStyler = (td, columnName, columnValue, entity) => {
        if (columnName === 'project') {
            switch (columnValue) {
                case 'MSP':
                    this.makeTDValueToBadge(td, columnValue, CrudPage.BADGE_TYPES.BLUE);
                    break;
                case 'MSPR':
                    this.makeTDValueToBadge(td, columnValue, CrudPage.BADGE_TYPES.GREEN);
                    break;
            }
        }
        return td;
    }
}