function confirmCanddate(candidateId,  candidateName) {
    if (confirm("Are you sure you want to select "+candidateName+" as candidate?")) {
        document.getElementById('candidate_id').value = candidateId;
        document.getElementById('voteForm').submit();
    }
}