from flask import Flask, request, jsonify, render_template
from sentence_transformers import SentenceTransformer, util
import json
from flask_cors import CORS

app = Flask(__name__)
CORS(app)   # Enable CORS for all routes and origins

# Load the pre-trained embedding model
model = SentenceTransformer('sentence-transformers/all-MiniLM-L6-v2')

# Load questions and answers
with open('qa_data.json', 'r') as f:
    qa_pairs = json.load(f)

questions = [pair['question'] for pair in qa_pairs]
answers = [pair['answer'] for pair in qa_pairs]

# Encode all questions once
question_embeddings = model.encode(questions, convert_to_tensor=True)


@app.route('/chat', methods=['POST'])
def chat():
    user_message = request.json['message']
    user_embedding = model.encode(user_message, convert_to_tensor=True)

    # Compute cosine similarities
    similarities = util.cos_sim(user_embedding, question_embeddings)
    score, best_match_idx = similarities[0].max(), similarities.argmax().item()

    if score < 0.6:
        response = "Sorry, I’m not sure about that. Please ask something else."
    else:
        response = answers[best_match_idx]

    return jsonify({'reply': response})

if __name__ == '__main__':
    app.run(debug=True)
