function handleVote(element, targetId, isQuestion = true) {
    const currentUser = JSON.parse(localStorage.getItem('currentUser'));
    if (!currentUser) {
        window.location.href = 'login.html';
        return;
    }

    const voteType = element.classList.contains('upvote') ? 'up' : 'down';
    const data = {
        [isQuestion ? 'question_id' : 'answer_id']: targetId,
        vote_type: voteType
    };

    fetch('process_vote.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI based on vote action
            const voteCount = element.closest('.vote-container').querySelector('.vote-count');
            const currentCount = parseInt(voteCount.textContent);
            
            switch(data.action) {
                case 'added':
                    voteCount.textContent = currentCount + (voteType === 'up' ? 1 : -1);
                    element.classList.add('active');
                    break;
                case 'removed':
                    voteCount.textContent = currentCount - (voteType === 'up' ? 1 : -1);
                    element.classList.remove('active');
                    break;
                case 'updated':
                    voteCount.textContent = currentCount + (voteType === 'up' ? 2 : -2);
                    element.classList.add('active');
                    element.closest('.vote-container').querySelector(`.${voteType === 'up' ? 'downvote' : 'upvote'}`).classList.remove('active');
                    break;
            }
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing your vote');
    });
}

// Add event listeners to vote buttons
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.upvote, .downvote').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-id');
            const isQuestion = this.closest('.question') !== null;
            handleVote(this, targetId, isQuestion);
        });
    });
}); 