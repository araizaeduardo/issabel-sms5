<link rel="stylesheet" href="modules/sms/themes/default/css/styles.css">

<div class="dashboard-container">
    <!-- Config Section -->
    <div class="config-section {if $SHOW_CONFIG}visible{/if}">
        <h3>{$CONFIG_TITLE}</h3>
        <form method="POST" action="?menu=sms&action=save_config">
            <div class="form-group">
                <label for="api_key">{$API_KEY_LABEL}</label>
                <input type="password" id="api_key" name="api_key" value="{$CURRENT_API_KEY}" required>
            </div>
            <div class="form-group">
                <label for="phone_number">{$PHONE_NUMBER_LABEL}</label>
                <input type="text" id="phone_number" name="phone_number" value="{$CURRENT_PHONE_NUMBER}" required>
            </div>
            <button type="submit" class="button">{$SAVE_CONFIG}</button>
        </form>
    </div>

    <!-- Messages Dashboard -->
    <div class="dashboard-grid">
        <!-- Send Message Section -->
        <div class="send-message-section">
            <h3>{$SEND_MESSAGE_TITLE}</h3>
            <form method="POST" action="?menu=sms&action=send">
                <div class="form-group">
                    <label for="to_number">{$TO_NUMBER_LABEL}</label>
                    <input type="text" id="to_number" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="message">{$MESSAGE_LABEL}</label>
                    <textarea id="message" name="message" rows="4" required></textarea>
                </div>
                <button type="submit" class="button">{$SEND_BUTTON}</button>
            </form>
        </div>

        <!-- Messages List -->
        <div class="messages-section">
            <div class="messages-header">
                <h3>{$MESSAGES_TITLE}</h3>
                <div class="messages-tabs">
                    <button class="tab-button {if $CURRENT_TAB eq 'all'}active{/if}" data-tab="all">{$ALL_MESSAGES}</button>
                    <button class="tab-button {if $CURRENT_TAB eq 'received'}active{/if}" data-tab="received">{$RECEIVED_MESSAGES}</button>
                    <button class="tab-button {if $CURRENT_TAB eq 'sent'}active{/if}" data-tab="sent">{$SENT_MESSAGES}</button>
                </div>
            </div>
            
            <div class="messages-list">
                {foreach from=$MESSAGES item=message}
                <div class="message-item {$message.direction} {if !$message.read_status}unread{/if}">
                    <div class="message-header">
                        <span class="phone">{$message.phone}</span>
                        <span class="date">{$message.sent_date}</span>
                    </div>
                    <div class="message-content">{$message.message}</div>
                    <div class="message-status">
                        <span class="status-badge {$message.status}">{$message.status}</span>
                    </div>
                </div>
                {/foreach}
            </div>
            
            {if $TOTAL_PAGES > 1}
            <div class="pagination">
                {if $CURRENT_PAGE > 1}
                <a href="?menu=sms&page={$CURRENT_PAGE-1}&tab={$CURRENT_TAB}" class="page-link">&laquo; {$PREVIOUS}</a>
                {/if}
                
                {section name=pagination start=1 loop=$TOTAL_PAGES+1}
                    {if $smarty.section.pagination.index == $CURRENT_PAGE}
                    <span class="page-link active">{$smarty.section.pagination.index}</span>
                    {else}
                    <a href="?menu=sms&page={$smarty.section.pagination.index}&tab={$CURRENT_TAB}" class="page-link">{$smarty.section.pagination.index}</a>
                    {/if}
                {/section}
                
                {if $CURRENT_PAGE < $TOTAL_PAGES}
                <a href="?menu=sms&page={$CURRENT_PAGE+1}&tab={$CURRENT_TAB}" class="page-link">{$NEXT} &raquo;</a>
                {/if}
            </div>
            {/if}
        </div>
    </div>
</div>

<script src="modules/sms/themes/default/js/dashboard.js"></script>
