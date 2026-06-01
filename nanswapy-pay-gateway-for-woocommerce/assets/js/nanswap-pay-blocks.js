( function( wcBlocksRegistry, wcSettings, wpElement, wpHtmlEntities ) {
    var settings = wcSettings.getSetting( 'nanswap_pay_gateway_data', {} );
    var label     = wpHtmlEntities.decodeEntities( settings.title || 'Nanswap Pay' );
    var description = wpHtmlEntities.decodeEntities( settings.description || '' );
    var iconUrl   = settings.icon || '';

    var Label = function() {
        var children = [ label ];
        if ( iconUrl ) {
            children.unshift(
                wpElement.createElement( 'img', {
                    src: iconUrl,
                    alt: label,
                    style: { height: '24px', marginRight: '8px', verticalAlign: 'middle' }
                } )
            );
        }
        return wpElement.createElement( 'span', null, children );
    };

    var Content = function() {
        if ( ! description ) { return null; }
        return wpElement.createElement( 'p', null, description );
    };

    wcBlocksRegistry.registerPaymentMethod( {
        name: 'nanswap_pay_gateway',
        label: wpElement.createElement( Label, null ),
        content: wpElement.createElement( Content, null ),
        edit: wpElement.createElement( Content, null ),
        canMakePayment: function() { return true; },
        ariaLabel: label,
        supports: {
            features: settings.supports || [],
        },
    } );
} )(
    window.wc.wcBlocksRegistry,
    window.wc.wcSettings,
    window.wp.element,
    window.wp.htmlEntities
);
