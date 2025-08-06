<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>University Insurance FAQ Chatbot</title>
<link rel="stylesheet" href="styles.css">
<style>
  body {
   font-family: Arial, sans-serif;
    margin: 20px;
    background-color: #f9f9f9;
    color: #333;
  }

  
  header h1 {
    margin: 0;
    }
    h2 {
    text-align: center;
    
    
  }

  #savedFaqs {
    max-width: 700px;
    margin: 30px auto 50px auto;
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
  }

  #savedFaqs h2 {
    margin-top: 0;
    border-bottom: 2px solid #007bff;
    padding-bottom: 8px;
    color: #007bff;
  }

  #faqList {
    list-style: none;
    padding-left: 0;
    max-height: 400px;
    overflow-y: auto;
  }

  #faqList li {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    position: relative;
  }

  #faqList li strong {
    display: block;
    margin-bottom: 6px;
    color: #222;
  }

  .delete-btn {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    background: #dc3545;
    border: none;
    color: white;
    border-radius: 5px;
    padding: 5px 10px;
    cursor: pointer;
    font-size: 0.85rem;
    transition: background 0.3s ease;
  }
  .delete-btn:hover {
    background: #b02a37;
  }

  #chatbotIcon {
    position: fixed;
    bottom: 25px;
    right: 25px;
    width: 60px;
    height: 60px;
    background: #007bff;
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 6px 15px rgba(0,123,255,0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    transition: background 0.3s ease;
  }
  #chatbotIcon:hover {
    background: #0056b3;
  }
  #chatbotIcon svg {
    width: 30px;
    height: 30px;
    fill: white;
  }

  #chatbotPopup {
    position: fixed;
    bottom: 95px;
    right: 25px;
    width: 350px;
    max-height: 500px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    display: none;
    flex-direction: column;
    overflow: hidden;
    z-index: 1100;
  }

  #chatHeader {
    background: #007bff;
    color: white;
    padding: 15px 20px;
    font-weight: bold;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  #chatHeader button {
    background: transparent;
    border: none;
    color: white;
    font-size: 1.2rem;
    cursor: pointer;
    line-height: 1;
  }

  #chatbox {
    padding: 15px;
    flex-grow: 1;
    overflow-y: auto;
    background: #f9f9f9;
  }

  .chat-entry {
    margin-bottom: 12px;
  }
  .chat-entry .user {
    color: #007bff;
    font-weight: 600;
  }
  .chat-entry .bot {
    color: #28a745;
    font-weight: 600;
  }

  #inputContainer {
    padding: 10px 15px;
    border-top: 1px solid #ddd;
    display: flex;
  }

  #questionInput {
    flex-grow: 1;
    padding: 10px 15px;
    font-size: 1rem;
    border: 1px solid #ccc;
    border-radius: 25px;
    outline: none;
  }

  #sendBtn {
    margin-left: 10px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 25px;
    padding: 10px 18px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.3s ease;
  }
  #sendBtn:hover {
    background: #0056b3;
  }
</style>
</head>
<body>
<header>
    <h1>Resource Allocation App</h1>
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="add_people.php">Add people</a></li>
            <li><a href="resources.php">Resources</a></li>
            <li><a href="budget.php">Budget</a></li>
            <li><a href="tickets.php">Tickets</a></li>
            <li><a href="work_orders.php">Work Orders</a></li>
            <li><a href="faq_chatbot.php">Student Health Insurance</a></li>
            <li><a href="refunds.php">Refunds</a></li>
            <li><a href="agent_compensation.php">Agent Compensation</a></li>
            <li><a href="students.php">Students</a></li>
            <li><a href="workers_invoice.php">Workers Invoice</a></li>
        </ul>
    </nav>
</header>
<h2>University Insurance FAQ</h2>

<div id="savedFaqs">
  <h2>Saved FAQs</h2>
  <ul id="faqList">
  </ul>
</div>

<div id="chatbotIcon" title="Open Chatbot" aria-label="Open Chatbot" role="button" tabindex="0">

  <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
    <path d="M20 2H4C2.89 2 2 2.89 2 4v14c0 1.1.89 2 2 2h14l4 4V4c0-1.11-.89-2-2-2z"/>
  </svg>
</div>


<div id="chatbotPopup" aria-live="polite" aria-label="Chatbot window">
  <div id="chatHeader">
    <span>Chatbot</span>
    <button id="closeChat" aria-label="Close Chatbot">&times;</button>
  </div>
  <div class="chatbox" id="chatbox"></div>
  <div id="inputContainer">
    <input type="text" id="questionInput" placeholder="Ask your question..." aria-label="Chat input" />
    <button id="sendBtn">Send</button>
  </div>
</div>

<script>
  const chatbotIcon = document.getElementById('chatbotIcon');
  const chatbotPopup = document.getElementById('chatbotPopup');
  const closeChatBtn = document.getElementById('closeChat');
  const chatbox = document.getElementById('chatbox');
  const input = document.getElementById('questionInput');
  const sendBtn = document.getElementById('sendBtn');
  const faqList = document.getElementById('faqList');
  const savedFaqs = document.getElementById('savedFaqs');

  chatbotIcon.addEventListener('click', () => {
    chatbotPopup.style.display = 'flex';
    input.focus();
  });

  closeChatBtn.addEventListener('click', () => {
    chatbotPopup.style.display = 'none';
  });


  sendBtn.addEventListener('click', sendMessage);
  input.addEventListener('keypress', e => {
    if (e.key === 'Enter') sendMessage();
  });

  function sendMessage() {
    const message = input.value.trim();
    if (!message) return;


    appendMessage('user', message);

    input.value = '';
    chatbox.scrollTop = chatbox.scrollHeight;

    fetch('http://127.0.0.1:5000/chat', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message })
    })
    .then(res => res.json())
    .then(data => {
      appendMessage('bot', data.reply);
      addFaqButton(message, data.reply);
    })
    .catch(() => {
      appendMessage('bot', 'Failed to connect to chatbot server.');
    });
  }

  function appendMessage(sender, text) {
    const div = document.createElement('div');
    div.classList.add('chat-entry');
    const span = document.createElement('span');
    span.className = sender;
    span.textContent = sender === 'user' ? `You: ${text}` : `Bot: ${text}`;
    div.appendChild(span);
    chatbox.appendChild(div);
    chatbox.scrollTop = chatbox.scrollHeight;
  }

  function addFaqButton(question, answer) {
    
    const lastBotMsg = chatbox.lastElementChild;
    if (!lastBotMsg) return;

    const btn = document.createElement('button');
    btn.textContent = 'Make FAQ';
    btn.className = 'make-faq-btn';
    btn.style.marginLeft = 'auto';
    btn.style.marginTop = '5px';

    btn.onclick = () => {
      addFaqToList(question, answer);
      saveFaqToFile(question, answer);
      btn.disabled = true;
      btn.textContent = 'Saved';
    };

    lastBotMsg.appendChild(btn);
  }

  function addFaqToList(question, answer) {
    savedFaqs.style.display = 'block';


    const exists = Array.from(faqList.children).some(li => {
      return li.dataset.question === question;
    });
    if (exists) return;

    const li = document.createElement('li');
    li.dataset.question = question;
    li.innerHTML = `<strong>${question}</strong><br>${answer}`;

    const delBtn = document.createElement('button');
    delBtn.className = 'delete-btn';
    delBtn.textContent = 'Delete';
    delBtn.onclick = () => {
      li.remove();
      deleteFaq(question);
    };

    li.appendChild(delBtn);
    faqList.appendChild(li);
  }

  
  function saveFaqToFile(question, answer) {
    fetch('save_faqs.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ question, answer })
    });
  }


  function deleteFaq(questionToDelete) {
    fetch('load_faqs.php')
      .then(res => res.json())
      .then(faqs => {
        const updated = faqs.filter(faq => faq.question !== questionToDelete);
        return fetch('save_faqs.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(updated)
        });
      });
  }


  window.addEventListener('DOMContentLoaded', () => {
    fetch('load_faqs.php')
      .then(res => res.json())
      .then(faqs => {
        faqs.forEach(faq => {
          addFaqToList(faq.question, faq.answer);
        });
      });
  });
</script>

</body>
</html>
