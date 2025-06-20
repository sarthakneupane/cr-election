function confirmVote(candidateId,  candidateName) {
    if (confirm("Are you sure you want to vote for "+candidateName+"?")) {
        document.getElementById('candidate_id').value = candidateId;
        document.getElementById('voteForm').submit();
    }
}

