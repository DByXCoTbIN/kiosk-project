// Touch Optimizations and Virtual Keyboard for all pages
// This file contains touchscreen enhancements and virtual keyboard functionality

// Virtual Keyboard functionality
let currentInputElement = null;
let isShiftActive = false;

function showVirtualKeyboard(inputElement) {
    currentInputElement = inputElement;
    const keyboard = document.getElementById('virtual-keyboard');
    if (!keyboard) {
        createVirtualKeyboard();
    }

    const finalKeyboard = document.getElementById('virtual-keyboard');
    finalKeyboard.classList.add('show');

    // Prevent mobile keyboard from showing
    inputElement.setAttribute('readonly', 'readonly');
    inputElement.blur();

    // Add keyboard event listeners
    setupKeyboardEvents();
}

function hideVirtualKeyboard() {
    const keyboard = document.getElementById('virtual-keyboard');
    if (keyboard) {
        keyboard.classList.remove('show');
    }

    if (currentInputElement) {
        currentInputElement.removeAttribute('readonly');
        currentInputElement = null;
    }

    // Remove keyboard event listeners
    removeKeyboardEvents();
}

function createVirtualKeyboard() {
    const keyboardHTML = `
        <div id="virtual-keyboard" class="virtual-keyboard">
            <div class="keyboard-header">
                <span class="keyboard-title">Экранная клавиатура</span>
                <button class="keyboard-close" onclick="hideVirtualKeyboard()">✕</button>
            </div>
            <div class="keyboard-layout">
                <!-- First row -->
                <div class="keyboard-row">
                    <button class="key" data-key="й">й</button>
                    <button class="key" data-key="ц">ц</button>
                    <button class="key" data-key="у">у</button>
                    <button class="key" data-key="к">к</button>
                    <button class="key" data-key="е">е</button>
                    <button class="key" data-key="н">н</button>
                    <button class="key" data-key="г">г</button>
                    <button class="key" data-key="ш">ш</button>
                    <button class="key" data-key="щ">щ</button>
                    <button class="key" data-key="з">з</button>
                    <button class="key" data-key="х">х</button>
                    <button class="key" data-key="ъ">ъ</button>
                </div>
                <!-- Second row -->
                <div class="keyboard-row">
                    <button class="key" data-key="ф">ф</button>
                    <button class="key" data-key="ы">ы</button>
                    <button class="key" data-key="в">в</button>
                    <button class="key" data-key="а">а</button>
                    <button class="key" data-key="п">п</button>
                    <button class="key" data-key="р">р</button>
                    <button class="key" data-key="о">о</button>
                    <button class="key" data-key="л">л</button>
                    <button class="key" data-key="д">д</button>
                    <button class="key" data-key="ж">ж</button>
                    <button class="key" data-key="э">э</button>
                </div>
                <!-- Third row -->
                <div class="keyboard-row">
                    <button class="key shift-key" data-key="shift">⇧</button>
                    <button class="key" data-key="я">я</button>
                    <button class="key" data-key="ч">ч</button>
                    <button class="key" data-key="с">с</button>
                    <button class="key" data-key="м">м</button>
                    <button class="key" data-key="и">и</button>
                    <button class="key" data-key="т">т</button>
                    <button class="key" data-key="ь">ь</button>
                    <button class="key" data-key="б">б</button>
                    <button class="key" data-key="ю">ю</button>
                    <button class="key backspace-key" data-key="backspace">⌫</button>
                </div>
                <!-- Fourth row -->
                <div class="keyboard-row">
                    <button class="key space-key" data-key=" ">Пробел</button>
                    <button class="key enter-key" data-key="enter">↵</button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', keyboardHTML);
}

function setupKeyboardEvents() {
    const keys = document.querySelectorAll('.key');

    keys.forEach(key => {
        key.addEventListener('click', handleKeyPress);
    });
}

function removeKeyboardEvents() {
    const keys = document.querySelectorAll('.key');

    keys.forEach(key => {
        key.removeEventListener('click', handleKeyPress);
    });
}

function handleKeyPress(e) {
    e.preventDefault();

    if (!currentInputElement) return;

    const key = e.target.dataset.key;
    const keyElement = e.target;

    // Add visual feedback
    keyElement.classList.add('pressed');
    setTimeout(() => {
        keyElement.classList.remove('pressed');
    }, 150);

    switch (key) {
        case 'backspace':
            // Delete last character
            const currentTextBackspace = currentInputElement.textContent || '';
            currentInputElement.textContent = currentTextBackspace.slice(0, -1);
            break;

        case 'enter':
            // Save and exit edit mode (for admin editing)
            if (currentInputElement.classList.contains('editable-group-name')) {
                const groupId = currentInputElement.dataset.groupId;
                if (typeof saveGroupName === 'function') {
                    saveGroupName(groupId);
                }
            }
            hideVirtualKeyboard();
            break;

        case 'shift':
            // Toggle shift state
            isShiftActive = !isShiftActive;
            keyElement.classList.toggle('active', isShiftActive);
            break;

        default:
            // Add character
            let char = key;
            if (isShiftActive) {
                // Convert to uppercase for Russian letters
                char = char.toUpperCase();
                // Reset shift after use
                isShiftActive = false;
                document.querySelector('.shift-key').classList.remove('active');
            }

            // Insert character at cursor position or at end
            const currentText = currentInputElement.textContent || '';
            const selection = window.getSelection();
            const range = selection.getRangeAt(0);

            if (range.collapsed) {
                // No selection, insert at cursor
                const cursorPos = range.startOffset;
                const beforeText = currentText.substring(0, cursorPos);
                const afterText = currentText.substring(cursorPos);
                currentInputElement.textContent = beforeText + char + afterText;

                // Restore cursor position
                setTimeout(() => {
                    const newRange = document.createRange();
                    newRange.setStart(currentInputElement.firstChild || currentInputElement, cursorPos + 1);
                    newRange.setEnd(currentInputElement.firstChild || currentInputElement, cursorPos + 1);
                    selection.removeAllRanges();
                    selection.addRange(newRange);
                }, 0);
            } else {
                // Replace selection
                range.deleteContents();
                range.insertNode(document.createTextNode(char));
            }
            break;
    }

    // Trigger input event for any listeners
    currentInputElement.dispatchEvent(new Event('input', { bubbles: true }));
}

// Enhanced group name editing with virtual keyboard
function startGroupNameEditing(e) {
    e.stopPropagation(); // Prevent modal opening

    const nameElement = e.target;
    const groupId = nameElement.dataset.groupId;
    const groupCell = nameElement.closest('.group-cell');

    // If already editing, don't start again
    if (groupCell.classList.contains('editing')) return;

    // Store original value
    if (typeof originalValues !== 'undefined') {
        originalValues[groupId] = nameElement.textContent.trim();
    }

    // Enable editing mode
    groupCell.classList.add('editing');
    nameElement.contentEditable = 'true';
    nameElement.focus();

    // Select all text
    const range = document.createRange();
    range.selectNodeContents(nameElement);
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(range);
}

// Close keyboard when clicking outside
document.addEventListener('click', function(e) {
    const keyboard = document.getElementById('virtual-keyboard');
    const isKeyboard = keyboard && keyboard.contains(e.target);
    const isEditable = e.target.classList.contains('editable-group-name') || e.target.closest('.group-cell.editing');

    if (!isKeyboard && !isEditable && keyboard && keyboard.classList.contains('show')) {
        hideVirtualKeyboard();
    }
});

// Handle touch events for better mobile experience
document.addEventListener('touchstart', function(e) {
    const keyboard = document.getElementById('virtual-keyboard');
    const isKeyboard = keyboard && keyboard.contains(e.target);
    const isEditable = e.target.classList.contains('editable-group-name') || e.target.closest('.group-cell.editing');

    if (!isKeyboard && !isEditable && keyboard && keyboard.classList.contains('show')) {
        e.preventDefault();
        hideVirtualKeyboard();
    }
}, { passive: false });

// Prevent zoom on double tap
let lastTouchEnd = 0;
document.addEventListener('touchend', function(e) {
    const now = (new Date()).getTime();
    if (now - lastTouchEnd <= 300) {
        e.preventDefault();
    }
    lastTouchEnd = now;
}, false);

// Auto-show virtual keyboard for touch devices on input focus
document.addEventListener('focusin', function(e) {
    const target = e.target;

    // Check if it's an editable element that should trigger virtual keyboard
    if ((target.contentEditable === 'true' || target.tagName === 'INPUT' || target.tagName === 'TEXTAREA') &&
        'ontouchstart' in window) {

        // Small delay to ensure element is ready
        setTimeout(() => {
            showVirtualKeyboard(target);
        }, 100);
    }
});

// Initialize touch optimizations when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Add touch event listeners to editable elements
    const editableElements = document.querySelectorAll('.editable-group-name');
    editableElements.forEach(element => {
        element.addEventListener('click', startGroupNameEditing);
    });

    // Add touch feedback to all interactive elements
    const interactiveElements = document.querySelectorAll('button, input, select, textarea, .clickable-cell, .admin-btn, .logout');
    interactiveElements.forEach(element => {
        element.addEventListener('touchstart', function() {
            this.style.transform = 'scale(0.98)';
            this.style.opacity = '0.8';
        });

        element.addEventListener('touchend', function() {
            this.style.transform = '';
            this.style.opacity = '';
        });
    });
});

// Export functions for global use
window.showVirtualKeyboard = showVirtualKeyboard;
window.hideVirtualKeyboard = hideVirtualKeyboard;
window.startGroupNameEditing = startGroupNameEditing;
