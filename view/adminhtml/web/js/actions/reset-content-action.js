define([
    'jquery',
    'underscore',
    'MageOS_CatalogDataAI/js/actions/core-action',
], function ($, _, catalogDataAiCore) {
    'use strict';

    function resetContent(element) {
        this.resetValueToPrevious(catalogDataAiCore.getTarget(element));
    }

    function resetValueToPrevious(target) {
        catalogDataAiCore.updateTargetValue(target, catalogDataAiCore.getPreviousValue())
    }

    return {
        resetContent: resetContent,
        resetValueToPrevious: resetValueToPrevious,
    };
});
