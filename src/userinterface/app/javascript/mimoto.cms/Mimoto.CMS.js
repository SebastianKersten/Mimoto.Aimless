/**
 * Mimoto.CMS
 *
 * @author Sebastian Kersten (@supertaboo)
 */

'use strict';

module.exports = function()
{
    // start
    this.__construct();
};

module.exports.prototype = {


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
    // --- Public methods --------------------------------------------------------
    // ----------------------------------------------------------------------------


    /**
     * Create new entity
     */
    entityNew: function()
    {
        var popup = Mimoto.popup.open("/mimoto.cms/entity/new");

        //popup.on('success') = popup.close();
    },

    entityCreate: function(data)
    {
        $.ajax({
            type: 'POST',
            url: "/mimoto.cms/entity/create",
            data: data,
            dataType: 'json'
        }).done(function(data) {
            Mimoto.popup.close();
        });
    },

    entityView: function(nEntityId)
    {
        window.open('/mimoto.cms/entity/' + nEntityId + '/view', '_self');
    },

    entityEdit: function(nEntityId)
    {
        Mimoto.popup.open('/mimoto.cms/entity/' + nEntityId + '/edit');
    },

    entityUpdate: function(nEntityId, data)
    {
        $.ajax({
            type: 'POST',
            url: "/mimoto.cms/entity/" + nEntityId + "/update",
            data: data,
            dataType: 'json'
        }).done(function(data) {
            Mimoto.popup.close();
        });
    },

    entityDelete: function(nEntityId, sEntityName)
    {
        var response = confirm("Are you sure you want to delete the entity '" + sEntityName + "'?\n\nALL DATA WILL BE LOST!!\n\n(Really! I'm not kidding!)");
        if (response == true) {
            $.ajax({
                type: 'GET',
                url: "/mimoto.cms/entity/" + nEntityId + "/delete",
                //data: data,
                dataType: 'json'
            }).done(function(data) {
                window.open('/mimoto.cms/entities', '_self');
            });
        }
    },

    entityPropertyNew: function(nEntityId)
    {
        Mimoto.popup.open("/mimoto.cms/entity/" + nEntityId + "/property/new");
    },

    entityPropertyCreate: function(nEntityId, data)
    {
        $.ajax({
            type: 'POST',
            url: "/mimoto.cms/entity/" + nEntityId + "/property/create",
            data: data,
            dataType: 'json'
        }).done(function(data) {
            Mimoto.popup.close();
        });
    },

    entityPropertyEdit: function(nEntityPropertyId)
    {
        Mimoto.popup.open("/mimoto.cms/entityproperty/" + nEntityPropertyId + "/edit");
    },



    notificationClose: function(sEntityType, nNotificationId)
    {
        // 8. find field
        var aNotifications = $("[mls_id='" + sEntityType + '.' + nNotificationId + "']");

        // 9. collect value
        aNotifications.each( function(index, $component) {
            // init
            $($component).remove();
        });

        // 11. send data
        $.ajax({
            type: 'GET',
            url: '/mimoto.cms/notifications/' + nNotificationId + '/close',
            data: null,
            dataType: 'json',
            success: function(resultData, resultStatus, resultSomething)
            {
                console.log(resultData);
                console.log(resultStatus);
                console.log(resultSomething);
            }
        });
    }



    // ----------------------------------------------------------------------------
    // --- Private methods --------------------------------------------------------
    // ----------------------------------------------------------------------------



}