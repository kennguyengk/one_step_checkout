/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Payment
 * @package    Red_Star_Solution
 * @copyright  Copyright (c) 2015 KenNguyen <teogk89@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
SoftprodigyAccordion = Class.create();
SoftprodigyAccordion.prototype = {
    initialize: function(elem, clickableEntity, checkAllow) {
        this.container = $(elem);
        this.checkAllow = checkAllow || false;
        this.disallowAccessToNextSections = false;
        this.sections = $$('#' + elem + ' .section');
        this.currentSection = false;
        this.headers = $$('#' + elem + ' .section ' + clickableEntity);
//        this.headers.each(function(header) {
//            Event.observe(header,'click',this.sectionClicked.bindAsEventListener(this));
//        }.bind(this));
    },

    setContainer: function(elm) {
        this.container = $(elm);
    },

    setSections: function(selector) {
        this.sections = $$(selector);
    },

    setHeaders: function(selector) {
        this.headers = $$(selector);
        this.headers.each(function(header) {
            Event.observe(header,'click',this.sectionClicked.bindAsEventListener(this));
        }.bind(this));
    },

    sectionClicked: function(event) {
        this.openSection($(Event.element(event)).up('.section'));
        Event.stop(event);
    },

    openSection: function(section) {
        var section = $(section);

        jQuery('html, body').animate({
            scrollTop: jQuery("#checkoutSteps").offset().top
        }, 700);

        // Check allow
        if (this.checkAllow && !Element.hasClassName(section, 'allow')){
            return;
        }

        if(section.id != this.currentSection) {
            this.closeExistingSection();
            this.currentSection = section.id;
            $(this.currentSection).addClassName('active');
            $$("."+this.currentSection).first().addClassName('active');

            var contents = Element.select(section, '.a-item');
            contents[0].show();
            //Effect.SlideDown(contents[0], {duration:.2});

            if (this.disallowAccessToNextSections) {
                var pastCurrentSection = false;
                for (var i=0; i<this.sections.length; i++) {
                    if (pastCurrentSection) {
                        Element.removeClassName(this.sections[i], 'allow');
                        $$('.'+this.sections[i]).first().removeClassName('allow');
                    }
                    if (this.sections[i].id==section.id) {
                        pastCurrentSection = true;
                    }
                }
            }
        }
    },

    closeSection: function(section) {
        $(section).removeClassName('active');
        $$('.'+section).first().removeClassName('active');
        var contents = Element.select(section, '.a-item');
        contents[0].hide();
        //Effect.SlideUp(contents[0]);
    },

    openNextSection: function(setAllow){
        for (section in this.sections) {
            var nextIndex = parseInt(section)+1;
            if (this.sections[section].id == this.currentSection && this.sections[nextIndex]){
                if (setAllow) {
                    Element.addClassName(this.sections[nextIndex], 'allow');
                    $$('.'+this.sections[nextIndex]).first().addClassName('allow');
                }
                this.openSection(this.sections[nextIndex]);
                return;
            }
        }
    },

    openPrevSection: function(setAllow){
        for (section in this.sections) {
            var prevIndex = parseInt(section)-1;
            if (this.sections[section].id == this.currentSection && this.sections[prevIndex]){
                if (setAllow) {
                    Element.addClassName(this.sections[prevIndex], 'allow');
                    $$('.'+this.sections[prevIndex]).first().addClassName('allow');
                }
                this.openSection(this.sections[prevIndex]);
                return;
            }
        }
    },

    closeExistingSection: function() {
        if(this.currentSection) {
            this.closeSection(this.currentSection);
        }
    }
}
