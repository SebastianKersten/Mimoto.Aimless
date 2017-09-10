/**
 * Mimoto.CMS
 *
 * @author Sebastian Kersten (@supertaboo)
 */

'use strict';


let Header = require('./components/Header');
let TabMenuService = require('./components/TabMenuService');


module.exports = function()
{
    // start
    this.__construct();
};

module.exports.prototype = {


    // caching
    version: null,

    // interface
    _header: null,



    // ----------------------------------------------------------------------------
    // --- Constructor ------------------------------------------------------------
    // ----------------------------------------------------------------------------


    /**
     * Constructor
     */
    __construct: function ()
    {

    },



    // ----------------------------------------------------------------------------
    // --- Public methods ---------------------------------------------------------
    // ----------------------------------------------------------------------------


    startup: function()
    {
        // setup
        this._setupInterface();
    },



    // ----------------------------------------------------------------------------
    // --- Private methods --------------------------------------------------------
    // ----------------------------------------------------------------------------


    _setupInterface: function()
    {
        // register
        let navigation = document.querySelector('[data-mimotocms-navigation]');
        let header = document.querySelector('[data-mimotocms-header]');

        // init
        if (navigation && header) { this._header = new Header(header); }

        // setup tabmenus
        new TabMenuService();
    },



    // ----------------------------------------------------------------------------
    // --- Public methods - entity ------------------------------------------------
    // ----------------------------------------------------------------------------



    instanceDeleteAll:  function(sEntityType)
    {
        var response = confirm("Are you sure you want to delete ALL instances of type '" + sEntityType + "'?");
        if (response == true) {
            // 11. send data
            Mimoto.utils.callAPI({
                type: 'get',
                url: "/mimoto.cms/instance/" + sEntityType + "/all/delete",
                success: function (resultData, resultStatus, resultSomething) {
                    console.log(resultData);
                }
            });
        }
    },


    componentView: function(nComponentId)
    {
        window.open('/mimoto.cms/component/' + nComponentId + '/view', '_self');
    },

    layoutView: function(nLayoutId)
    {
        window.open('/mimoto.cms/layout/' + nLayoutId + '/view', '_self');
    },
    
    selectionView: function(nSelectionId)
    {
        window.open('/mimoto.cms/selection/' + nSelectionId + '/view', '_self');
    },


    /**
     * Formatting options
     */
    formattingOptionView: function(nItemId)
    {
        window.open('/mimoto.cms/configuration/formattingOption/' + nItemId + '/view', '_self');
    },



    formattingOptionDelete: function(nItemId, sFormattingOptionName)
    {
        var response = confirm("Are you sure you want to delete the formatting option '" + sFormattingOptionName + "'?\n\nALL DATA FROM THAT PROPERTY WILL BE LOST!!\n\n(like, forever ..)");
        if (response == true)
        {
            Mimoto.utils.callAPI({
                type: 'get',
                url: '/mimoto.cms/configuration/formattingOption/' + nItemId + '/delete',
                data: null,
                dataType: 'json',
                success: function (resultData, resultStatus, resultSomething) {
                    console.log(resultData);
                }
            });
        }
    },

    formattingOptionAttributeNew: function(nItemId)
    {
        var popup = Mimoto.popup('/mimoto.cms/formattingOption/' + nItemId + '/formattingOptionAttribute/new');
    },

    formattingOptionAttributeEdit: function(nItemId)
    {
        var popup = Mimoto.popup('/mimoto.cms/formattingOptionAttribute/' + nItemId + '/edit');
    },

    formattingOptionAttributeDelete: function(nItemId, sFormattingOptionName)
    {
        var response = confirm("Are you sure you want to delete the formatting option '" + sFormattingOptionName + "'?\n\nALL DATA FROM THAT PROPERTY WILL BE LOST!!\n\n(like, forever ..)");
        if (response == true)
        {
            Mimoto.utils.callAPI({
                type: 'get',
                url: '/mimoto.cms/formattingOptionAttribute/' + nItemId + '/delete',
                data: null,
                dataType: 'json',
                success: function (resultData, resultStatus, resultSomething) {
                    console.log(resultData);
                }
            });
        }
    },


    /**
     * User roles
     */
    userRoleNew: function()
    {
        var popup = Mimoto.popup("/mimoto.cms/configuration/userRole/new");
    },

    userRoleView: function(nItemId)
    {
        window.open('/mimoto.cms/configuration/userRole/' + nItemId + '/view', '_self');
    },

    userRoleEdit: function(nItemId)
    {
        Mimoto.popup('/mimoto.cms/configuration/userRole/' + nItemId + '/edit');
    },

    userRoleDelete: function(nItemId, sUserRoleName)
    {
        var response = confirm("Are you sure you want to delete the user role '" + sUserRoleName + "'?\n\nALL DATA FROM THAT PROPERTY WILL BE LOST!!\n\n(like, forever ..)");
        if (response == true)
        {
            Mimoto.utils.callAPI({
                type: 'get',
                url: '/mimoto.cms/configuration/userRole/' + nItemId + '/delete',
                data: null,
                dataType: 'json',
                success: function (resultData, resultStatus, resultSomething) {
                    console.log(resultData);
                }
            });
        }
    },


    pageView: function(nItemId)
    {
        window.open('/mimoto.cms/page/' + nItemId + '/view', '_self');
    },



    /**
     * Users
     */
    userNew: function()
    {
        var popup = Mimoto.popup("/mimoto.cms/user/new");
    },

    userView: function(nUserId)
    {
        window.open('/mimoto.cms/user/' + nUserId + '/view', '_self');
    },

    userEdit: function(nUserId)
    {
        Mimoto.popup('/mimoto.cms/user/' + nUserId + '/edit');
    },

    userDelete: function(nUserId)
    {
        Mimoto.utils.callAPI({
            type: 'get',
            url: '/mimoto.cms/user/' + nUserId + '/delete',
            data: null,
            dataType: 'json',
            success: function(resultData, resultStatus, resultSomething) {
                console.log(resultData);
            }
        });
    },
    

    
    /**
     * Content sections
     */
    contentNew: function(nContentId)
    {
        //Mimoto.page.open('/mimoto.cms/content/' + nContentId + '/new');
        window.open('/mimoto.cms/content/' + nContentId + '/new', '_self');
    },


    
    formFieldNew_TypeSelector: function(nFormId)
    {
        Mimoto.popup('/mimoto.cms/form/' + nFormId + '/field/new');
    },
    
    formFieldNew_FieldForm: function(nFormId, nFormFieldTypeId)
    {
        console.log('formFieldNew_FieldForm: nFormId=' + nFormId + ', nFormFieldTypeId=' + nFormFieldTypeId);
        
        Mimoto.popup.replace('/mimoto.cms/form/' + nFormId + '/field/new/' + nFormFieldTypeId);
    },
    
    formFieldEdit: function(nFormFieldTypeId, nFormFieldId)
    {
        window.open('/mimoto.cms/formfield/' + nFormFieldTypeId + '/' + nFormFieldId + '/edit', '_self');
    },
    
    formFieldDelete:  function(nFormFieldTypeId, nFormFieldId)
    {
        Mimoto.utils.callAPI({
            type: 'get',
            url: "/mimoto.cms/formfield/" + nFormFieldTypeId + '/' + nFormFieldId + '/delete',
            data: null,
            dataType: 'json',
            success: function(resultData, resultStatus, resultSomething) {
                console.log(resultData);
            }
        });
    },
    
    formFieldListItemAdd: function(sInputFieldType, sInputFieldId, sPropertySelector)
    {
        // 1. build
        var sURL = '/mimoto.cms/formfield/' + sInputFieldType + '/' + sInputFieldId + '/add/' + sPropertySelector;
        
        console.log(sURL);
        
        var popup = Mimoto.popup(sURL);
        
        // 1. return root of the popup (or root object)
        // 2. connect content of the popup (onload) to the popup object
        // 3. dispatchSuccess
        // 4. handle success
        // 5. do not autoconnect
        // 6. add new value to list
        
        // popup.success = function()
        // {
        //
        // }
    },
    
    formFieldListItemEdit: function(nConnectionId, sInstanceType, sInstanceId)
    {
        // search
        var listInfo = this.findListByListItem(nConnectionId, sInstanceType, sInstanceId);
        
        // execute
        window.open("/mimoto.cms/formfield/" + listInfo.sInputFieldType + "/" + listInfo.sInputFieldId + "/edit/" + listInfo.sPropertySelector + '/' + sInstanceType + '/' + sInstanceId, '_self');
    },
    
    
    formFieldListItemDelete: function(nConnectionId, sInstanceType, sInstanceId)
    {
        // search
        var listInfo = this.findListByListItem(nConnectionId, sInstanceType, sInstanceId);
        
        // execute
        var response = confirm("Are you sure you want to delete this item?");
        if (response == true) {
            Mimoto.utils.callAPI({
                type: 'get',
                url: '/mimoto.cms/formfield/' + listInfo.sInputFieldType + '/' + listInfo.sInputFieldId + '/remove/' + listInfo.sPropertySelector + '/' + sInstanceType + '/' + sInstanceId,
                data: null,
                dataType: 'json',
                success: function (resultData, resultStatus, resultSomething) {
                    console.log(resultData);
                }
            });
        }
    },
    
    findListByListItem: function(nConnectionId, sInstanceType, sInstanceId)
    {
        // init
        var listInfo = [];
        
        // search
        var $listItem = $('[data-mimoto-id="' + sInstanceType + '.' + sInstanceId + '"][data-mimoto-connection="' + nConnectionId + '"]');
        
        
        // validate
        if (!$listItem) { console.log('ListItem not found'); return; }
    
    
        // search
        var $parent = $($listItem).parent();
    
        // register
        var sInputFieldSelector = $($parent).attr('data-mimoto-list-id');
        
        // split
        var aInputFieldSelectorElements = sInputFieldSelector.split('.');
        
        // compose
        listInfo.sInputFieldType = aInputFieldSelectorElements[0];
        listInfo.sInputFieldId = aInputFieldSelectorElements[1];
        listInfo.sPropertySelector = sInputFieldSelector;
        
        // send
        return listInfo;
    }
    
}
