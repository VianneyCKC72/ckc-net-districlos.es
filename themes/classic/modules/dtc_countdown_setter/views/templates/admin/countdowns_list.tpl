<!-- views/templates/admin/countdowns_list.tpl -->
<div class="panel">
    <h3>{l s='Saved Countdowns'}</h3>
    {if empty($countdowns)}
        <p>{l s='No countdowns found.'}</p>
    {else}
        <table class="table">
            <thead>
                <tr>
                    <th>{l s='ID'}</th>
                    <th>{l s='Label'}</th>
                    <th>{l s='Start Date'}</th>
                    <th>{l s='End Date'}</th>
                    <th>{l s='Selectors'}</th>
                    <th>{l s='Active'}</th>
                    <th>{l s='Actions'}</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$countdowns item=countdown}
                    <tr>
                        <td>{$countdown.id_dtc_countdown_setter}</td>
                        <td>{$countdown.label}</td>
                        <td>{$countdown.start_date}</td>
                        <td>{$countdown.end_date}</td>
                        <td>{$countdown.selectors}</td>
                        <td>
                            {if $countdown.active}
                                <span class="label label-success">{l s='Yes'}</span>
                            {else}
                                <span class="label label-danger">{l s='No'}</span>
                            {/if}
                        </td>
                        <td>
                            <a href="{$link->getAdminLink('AdminModules', true)}&configure={$module_name}&toggleCountdown=1&id_dtc_countdown_setter={$countdown.id_dtc_countdown_setter}" class="btn btn-info">
                                {if $countdown.active}{l s='Deactivate'}{else}{l s='Activate'}{/if}
                            </a>
                            <a href="{$link->getAdminLink('AdminModules', true)}&configure={$module_name}&editCountdown=1&id_dtc_countdown_setter={$countdown.id_dtc_countdown_setter}" class="btn btn-default">
                                <i class="icon-pencil"></i> {l s='Edit'}
                            </a>
                            <a href="{$link->getAdminLink('AdminModules', true)}&configure={$module_name}&deleteCountdown=1&id_dtc_countdown_setter={$countdown.id_dtc_countdown_setter}" class="btn btn-danger" onclick="return confirm('{l s='Are you sure you want to delete this countdown?'}');">
                                <i class="icon-trash"></i> {l s='Delete'}
                            </a>
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    {/if}
    <a href="{$link->getAdminLink('AdminModules', true)}&configure={$module_name}&addNewCountdown=1" class="btn btn-primary">{l s='Add New Countdown'}</a>
</div> <!-- End of Panel -->
