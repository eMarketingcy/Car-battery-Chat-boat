document.addEventListener('DOMContentLoaded', () => {
    
    // Global State to track chat status
    const STATE = {
        isChatOpen: false,
        isLoading: false,
        messages: [],
        currentQuery: '' // Stores the initial query to append clarifications (e.g. "Diesel") later
    };

    /**
     * Initialize the Chatbot
     * Creates the floating bubble button on the page.
     */
    function init() {
        const chatBubble = document.createElement('button');
        chatBubble.className = 'cbc-bubble';
        chatBubble.setAttribute('aria-label', 'Open AI Battery Finder');
        
        // Chat Icon SVG
        chatBubble.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <span>AI Battery Finder</span>
        `;

        chatBubble.addEventListener('click', toggleChat);
        document.body.appendChild(chatBubble);
    }

    /**
     * Toggle Chat Window Visibility
     */
    function toggleChat() {
        STATE.isChatOpen = !STATE.isChatOpen;
        const existingChatbot = document.querySelector('.cbc-chatbot');
        
        if (STATE.isChatOpen && !existingChatbot) {
            createChatbotWindow();
        } else if (!STATE.isChatOpen && existingChatbot) {
            existingChatbot.remove();
        }
    }

    /**
     * Create the Main Chat Window DOM
     */
    function createChatbotWindow() {
        const chatbotContainer = document.createElement('div');
        chatbotContainer.className = 'cbc-chatbot';

        // 1. Header Section
        const header = document.createElement('div');
        header.className = 'cbc-header';
        header.innerHTML = `
            <h3>Car Battery Assistant</h3>
            <button aria-label="Close chat">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        `;
        header.querySelector('button').addEventListener('click', toggleChat);
        
        // 2. Messages Area (Scrollable)
        const messagesArea = document.createElement('div');
        messagesArea.className = 'cbc-messages';
        
        // 3. Input Area
        const inputArea = document.createElement('div');
        inputArea.className = 'cbc-input-area';
        inputArea.innerHTML = `
            <form>
                <input type="text" placeholder="e.g., VW Golf 2018 1.6 TDI" required />
                <button type="submit" aria-label="Send message" disabled>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                    </svg>
                </button>
            </form>
        `;

        // Bind Events
        const form = inputArea.querySelector('form');
        const input = inputArea.querySelector('input');
        const submitButton = inputArea.querySelector('button');

        input.addEventListener('input', () => {
            submitButton.disabled = input.value.trim() === '' || STATE.isLoading;
        });

        form.addEventListener('submit', handleSendMessage);

        // Assemble Window
        chatbotContainer.append(header, messagesArea, inputArea);
        document.body.appendChild(chatbotContainer);
        
        // Initial Greeting
        if (STATE.messages.length === 0) {
            addMessage({ sender: 'bot', content: "Hello! I'm your AI assistant from carbatteries.cy." });
            addMessage({ sender: 'bot', content: 'Please tell me the Make, Model, Year and Engine of your car.' });
        } else {
             renderAllMessages(); // Restore history if re-opened
        }
    }

    /**
     * Render all messages from history (useful when toggling chat)
     */
    function renderAllMessages() {
        const messagesArea = document.querySelector('.cbc-messages');
        if (!messagesArea) return;
        
        messagesArea.innerHTML = '';
        STATE.messages.forEach(msg => {
            messagesArea.appendChild(createMessageElement(msg));
        });
        scrollToBottom();
    }

    /**
     * Add a single message to state and DOM
     */
    function addMessage(message) {
        STATE.messages.push({ ...message, id: Date.now() + Math.random() });
        const messagesArea = document.querySelector('.cbc-messages');
        if (messagesArea) {
            messagesArea.appendChild(createMessageElement(message));
            scrollToBottom();
        }
    }

    /**
     * Create the DOM Element for a Message Bubble
     */
    function createMessageElement({ sender, content }) {
        const wrapper = document.createElement('div');
        wrapper.className = `cbc-message-wrapper ${sender}`;
        
        let messageContent = '';
        
        // Add Avatar for Bot
        if (sender === 'bot') {
            messageContent += `
                <div class="cbc-avatar">
                    <img src="https://carbatteries.cy/wp-content/uploads/2023/04/car-batteries-logo-icon-1.png" alt="Bot" />
                </div>`;
        }
        
        messageContent += `<div class="cbc-message">${content}</div>`;
        wrapper.innerHTML = messageContent;
        return wrapper;
    }

    /**
     * Generate HTML for a single Battery Result Card
     */
    function createBatteryResultElement(battery) {
        const dimensionsStr = `${battery.length || 'N/A'}x${battery.width || 'N/A'}x${battery.height || 'N/A'}`;
        const polarityText = battery.polarity === '1' ? '1 (Left+)' : '0 (Right+)';
        
        return `
            <div class="cbc-battery-result">
                <img src="${battery.imageUrl || 'https://via.placeholder.com/150'}" alt="${battery.name}" />
                <div>
                    <h4>${battery.name}</h4>
                    <p><strong>Specs:</strong> ${battery.ah}Ah, ${battery.cca}A CCA, ${battery.technology}</p>
                    <p><strong>Dimensions:</strong> ${dimensionsStr} mm</p>
                    <p><strong>Terminals:</strong> ${polarityText}</p>
                    <div class="cbc-battery-result-footer">
                        <p class="cbc-battery-price">€${battery.price.toFixed(2)}</p>
                        <a href="${battery.link}" target="_blank" rel="noopener noreferrer" class="cbc-battery-link">
                            View Product
                        </a>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Toggle Loading State (Spinner + Disable Input)
     */
    function setLoading(isLoading) {
        STATE.isLoading = isLoading;
        const input = document.querySelector('.cbc-input-area input');
        const button = document.querySelector('.cbc-input-area button');
        const loadingIndicator = document.querySelector('.cbc-loading');
        
        // Disable inputs while loading
        if (input) input.disabled = isLoading;
        if (button) button.disabled = isLoading || (input && input.value.trim() === '');
        
        if (isLoading && !loadingIndicator) {
            const messagesArea = document.querySelector('.cbc-messages');
            const loadingElement = createMessageElement({ 
                sender: 'bot', 
                content: '<div class="cbc-loading-dots"><span></span><span></span><span></span></div>' 
            });
            loadingElement.classList.add('cbc-loading');
            messagesArea.appendChild(loadingElement);
            scrollToBottom();
        } else if (!isLoading && loadingIndicator) {
            loadingIndicator.remove();
        }
    }
    
    /**
     * Global Handler for Clarification Buttons (Petrol/Diesel/Hybrid)
     * Attached to window so inline HTML onclicks work.
     */
    window.cbcOptionClick = function(optionValue) {
        // 1. Disable all option buttons to prevent multiple clicks
        const buttons = document.querySelectorAll('.cbc-option-btn');
        buttons.forEach(btn => btn.disabled = true);
        
        // 2. Append choice to previous query (e.g. "Toyota Yaris" + " " + "Hybrid")
        const detailedQuery = STATE.currentQuery + " " + optionValue;
        
        // 3. Inject into input and auto-submit
        const input = document.querySelector('.cbc-input-area input');
        if (input) {
            input.value = detailedQuery;
            const submitButton = input.form.querySelector('button');
            submitButton.disabled = false;
            submitButton.click();
        }
    };

    /**
     * Handle Form Submission (Sending Message to Server)
     */
    async function handleSendMessage(e) {
        e.preventDefault();
        const input = document.querySelector('.cbc-input-area input');
        const userMessage = input.value.trim();
        
        if (!userMessage || STATE.isLoading) return;

        // Store query for context (clarifications)
        STATE.currentQuery = userMessage;

        // Display User Message
        addMessage({ sender: 'user', content: userMessage });
        input.value = '';
        setLoading(true);

        try {
            const formData = new FormData();
            formData.append('action', 'get_battery_recommendation');
            formData.append('nonce', cbc_ajax.nonce);
            formData.append('car_info', userMessage);

            // Fetch from WordPress AJAX
            const response = await fetch(cbc_ajax.ajax_url, {
                method: 'POST',
                body: formData,
            });

            // Safe JSON parsing (handles server errors returning HTML)
            const text = await response.text();
            let result;
            try {
                result = JSON.parse(text);
            } catch (err) {
                console.error("Server Response Error:", text);
                throw new Error("Server returned an invalid response. Please try again.");
            }

            if (!result.success) {
                throw new Error(result.data.message || 'An unknown error occurred.');
            }
            
            const data = result.data;

            // --- CASE A: CLARIFICATION NEEDED ---
            if (data.type === 'clarification') {
                const { question, options } = data;
                
                // Build dynamic buttons
                let optionsHTML = '<div class="cbc-options-container">';
                if (options && Array.isArray(options)) {
                    options.forEach(opt => {
                        optionsHTML += `<button class="cbc-option-btn" type="button" onclick="cbcOptionClick('${opt}')">${opt}</button>`;
                    });
                }
                optionsHTML += '</div>';
                
                addMessage({ 
                    sender: 'bot', 
                    content: `<strong>${question}</strong><br>` + optionsHTML 
                });
                
            } 
            // --- CASE B: RESULTS FOUND ---
            else {
                const { specs, display_specs, batteries, view_more_url } = data; 
                
                const polarityText = display_specs.polarity === '1' ? '1 (Left+)' : '0 (Right+)';
                
                // --- FIX: Check for undefined/null values before displaying ---
                const ahText = (display_specs.ah && display_specs.ah > 0) ? display_specs.ah + 'Ah' : 'standard Ah';
                const ccaText = (display_specs.cca && display_specs.cca > 0) ? display_specs.cca + ' CCA' : 'standard CCA';
                const techText = display_specs.display_technology || 'Standard';

                // Bot Specs Message
                addMessage({ 
                    sender: 'bot', 
                    content: `I have analyzed your vehicle. You need a battery with approx <strong>${ahText}</strong> and <strong>${ccaText}</strong>. <br><br>Technology: <strong>${techText}</strong>, Polarity: <strong>${polarityText}</strong>. <br><br>Checking stock...` 
                });

                // Short delay for realism
                await new Promise(res => setTimeout(res, 600));

                if (batteries && batteries.length > 0) {
                     let batteryResultsHTML = batteries.map(createBatteryResultElement).join('');
                     
                     if (view_more_url) {
                        batteryResultsHTML += `<a href="${view_more_url}" target="_blank" class="cbc-view-more-link">View All Compatible Batteries</a>`;
                     }

                     addMessage({
                        sender: 'bot',
                        content: `<div><p style="margin-bottom: 8px; font-size: 0.875rem;">Here are the compatible batteries I found in our shop:</p>${batteryResultsHTML}</div>` + getDisclaimerHTML()
                    });
                } else {
                    addMessage({ 
                        sender: 'bot', 
                        content: "I'm sorry, I couldn't find any compatible batteries in our shop with those exact specifications. You can browse our full collection or contact support for more help." + getDisclaimerHTML()
                    });
                }
            }

        } catch (error) {
            const errorMessage = error.message || "An unexpected error occurred.";
            addMessage({ sender: 'bot', content: `Error: ${errorMessage}` + getDisclaimerHTML() });
        } finally {
            setLoading(false);
        }
    }

    /**
     * Auto-scroll chat to the bottom
     */
    function scrollToBottom() {
        const messagesArea = document.querySelector('.cbc-messages');
        if (messagesArea) {
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }
    }

    /**
     * Helper to build Contact Links
     */
    function getDisclaimerHTML() {
        try {
            const contacts = [];
            
            // Check PHP localized vars
            if (cbc_ajax.contact_phone) {
                contacts.push(`<a href="tel:${cbc_ajax.contact_phone}">Call</a>`);
            }
            if (cbc_ajax.contact_whatsapp) {
                const waNumber = cbc_ajax.contact_whatsapp.replace(/[^0-9]/g, '');
                contacts.push(`<a href="https://wa.me/${waNumber}" target="_blank">WhatsApp</a>`);
            }
            if (cbc_ajax.contact_email) {
                contacts.push(`<a href="mailto:${cbc_ajax.contact_email}">Email</a>`);
            }
            if (cbc_ajax.contact_page_url) {
                contacts.push(`<a href="${cbc_ajax.contact_page_url}" target="_blank">Contact Us</a>`);
            }

            if (contacts.length > 0) {
                let disclaimerHTML = '<div class="cbc-disclaimer">';
                disclaimerHTML += 'I am an AI assistant and can make mistakes. For guaranteed accuracy, please contact us:<br>';
                disclaimerHTML += contacts.join(' | ');
                disclaimerHTML += '</div>';
                return disclaimerHTML;
            }

        } catch (e) {
            console.error("Error building disclaimer:", e);
        }
        
        return ''; 
    }

    // Run Initialization
    init();
});