define([
    'jquery',
    'underscore',
    'MageOS_CatalogDataAI/js/actions/core-action',
], function ($, _, catalogDataAiCore) {
    'use strict';

    function generateContent(element) {
        const target = catalogDataAiCore.getTarget(element),
              productId = element.product_id,
              value = catalogDataAiCore.getTargetValue(target);

        catalogDataAiCore.setPreviousValue(value);

        this.getGeneratedAiContent(
            target,
            element.url,
            value,
            target.code,
            productId,
            element.store
        );
    }

    function getGeneratedAiContent(target, url, value, attributeCode, productId, store) {
        $.ajax({
            url: url,
            showLoader: true,
            data: {
                form_key: window.FORM_KEY,
                value,
                attribute_code: attributeCode,
                product_id: productId,
                store: store,
            },
            type: "POST",
            dataType : 'json',
            success: (result) => {
                let content = result.response?.message?.content ?? '';
                catalogDataAiCore.updateTargetValue(target, content);
            }
        });
    }

    return {
        generateContent: generateContent,
        getGeneratedAiContent: getGeneratedAiContent,
    };
});
