define([
    'jquery',
    'underscore',
    'uiRegistry',
    'tinymce',
], function ($, _, uiRegistry, tinyMCE) {
    'use strict';

    let targetPreviousValue = '';

    function getPreviousValue() {
        return targetPreviousValue;
    }
    function setPreviousValue(value) {
        targetPreviousValue = value;
    }

    function getTargetValue(target, value) {
        let wysiwygTarget = tinyMCE.get(target.wysiwygId);
        if (target.wysiwyg && wysiwygTarget !== null) {
            return wysiwygTarget.getContent();
        } else {
            return target.value();
        }
    }

    function updateTargetValue(target, value) {
        let wysiwygTarget = tinyMCE.get(target.wysiwygId);
        if (target.wysiwyg && wysiwygTarget !== null) {
            wysiwygTarget.setContent(value);
        } else {
            target
                .value(value)
                .trigger('change');
        }
    }

    function getTarget(element) {
        let fullTargetPath = element.parentName + "." + element.targetName;
        return uiRegistry.get(fullTargetPath);
    }

    return {
        getTarget: getTarget,
        updateTargetValue: updateTargetValue,
        getTargetValue: getTargetValue,
        getPreviousValue: getPreviousValue,
        setPreviousValue: setPreviousValue,
    };
});
