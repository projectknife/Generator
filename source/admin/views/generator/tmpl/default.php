<?php
/**
 * @package      com_pfdatagen
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2013 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die();
?>
<form action="<?php echo JRoute::_('index.php?option=com_pfdatagen'); ?>" method="post" name="adminForm" id="adminForm" autocomplete="off">
    <div id="j-main-container">
        <div class="well well-small">
            <fieldset class="form-horizontal" id="jform_setup">
                <p>
                    <?php echo JText::_('COM_PFDATAGEN_CREATE_TOTAL'); ?>:

                    <input type="text" name="total" id="jform_total" class="inputbox input-small" value="20"/>

                    <select name="model" id="jform_model" class="inputbox input-medium">
                        <?php foreach ($this->models AS $value => $title)
                        {
                            ?>
                            <option value="<?php echo $this->escape($value); ?>">
                                <?php echo $this->escape($title);?>
                            </option>
                            <?php
                        }
                        ?>
                    </select>

                    <?php echo JText::_('COM_PFDATAGEN_PER_REQUEST'); ?>:
                    <input type="text" id="jform_limit" name="limit" class="inputbox input-small" value="20"/>

                    <button type="button" class="btn btn-primary" onclick="PFdatagen.start()"><?php echo JText::_('COM_PFDATAGEN_START'); ?></button>
                </p>
            </fieldset>
            <fieldset class="form-horizontal" id="jform_progress" style="display: none;">
                <div class="progress" id="jform_prgcontainer">
                    <div class="bar bar-success" id="progress_bar" style="width: 24px;">
                        <span class="label label-success pull-right" id="progress_label">
                            0%
                        </span>
                    </div>
                </div>
            </fieldset>
        </div>

        <input type="hidden" name="task" value="generator.generate" />
        <input type="hidden" name="limitstart" id="jform_limitstart" value="0" />
        <input type="hidden" name="tmpl" value="component" />
        <input type="hidden" name="format" value="json" />
        <?php echo JHtml::_('form.token'); ?>
    </div>
</form>
<script type="text/javascript">
var PFdatagen =
{
    start: function()
    {
        jQuery('#jform_setup').hide();
        jQuery('#jform_progress').show();

        jQuery('#jform_limitstart'). val(0);

        PFdatagen.generate();
    },


    complete: function()
    {
        jQuery('#jform_progress').hide();
        jQuery('#jform_setup').show();

        jQuery('#jform_limitstart'). val(0);

        jQuery('#progress_bar').css('width', '24px');
    },


    generate: function()
    {
        var gen_form = jQuery('#adminForm');
        var gen_data = gen_form.serializeArray();

        jQuery('#jform_prgcontainer').addClass('active');
        jQuery('#jform_prgcontainer').addClass('progress-striped');

        jQuery.ajax(
        {
            url: gen_form.attr('action'),
            data: jQuery.param(gen_data),
            type: 'POST',
            processData: true,
            cache: false,
            dataType: 'json',

            success: function(rsp)
            {
                if (rsp.success == false) {
                    return false;
                }

                var limitstart = parseInt(jQuery('#jform_limitstart').val());
                var limit      = parseInt(jQuery('#jform_limit').val());
                var total      = parseInt(jQuery('#jform_total').val());

                limitstart = limitstart + limit;

                if (limitstart >= total) {
                    PFdatagen.complete();
                    return true;
                }

                // Update the progress bar
                var progress = limitstart * (100 / total);
                jQuery('#progress_bar').css('width', progress + '%');
                jQuery('#progress_label').text(parseInt(progress) + '%');

                jQuery('#jform_prgcontainer').removeClass('active');
                jQuery('#jform_prgcontainer').removeClass('progress-striped');

                jQuery('#jform_limitstart').val(limitstart);

                setTimeout("PFdatagen.generate()", 1000);
            },


            error: function(rsp, e, msg)
            {

            }
        });
    }
}
</script>