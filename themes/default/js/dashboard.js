document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tab = this.dataset.tab;
            window.location.href = `?menu=sms&tab=${tab}`;
        });
    });

    // Mark messages as read when viewed
    const unreadMessages = document.querySelectorAll('.message-item.unread');
    unreadMessages.forEach(message => {
        const messageId = message.dataset.messageId;
        fetch(`?menu=sms&action=mark_read&message_id=${messageId}`, {
            method: 'POST'
        });
    });

    // Config toggle
    const configToggle = document.getElementById('config-toggle');
    const configSection = document.querySelector('.config-section');
    
    if (configToggle && configSection) {
        configToggle.addEventListener('click', function() {
            configSection.classList.toggle('visible');
        });
    }

    // Phone number formatting
    const phoneInput = document.getElementById('to_number');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let number = e.target.value.replace(/\D/g, '');
            if (number.length > 0 && !number.startsWith('+')) {
                number = '+' + number;
            }
            e.target.value = number;
        });
    }

    // Message character counter
    const messageTextarea = document.getElementById('message');
    const charCounter = document.createElement('div');
    charCounter.className = 'char-counter';
    
    if (messageTextarea) {
        messageTextarea.parentNode.appendChild(charCounter);
        
        function updateCounter() {
            const remaining = 160 - messageTextarea.value.length;
            charCounter.textContent = `${remaining} characters remaining`;
            charCounter.style.color = remaining < 0 ? 'red' : '#666';
        }
        
        messageTextarea.addEventListener('input', updateCounter);
        updateCounter();
    }
});
