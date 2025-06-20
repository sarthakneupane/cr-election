function confirmSelection(crn, name) {
    if (confirm("Are you sure you want to select "+name+" as candidate?")) {
        document.getElementById('candidateId').value = crn;
        document.getElementById('candidateName').value = name;
        document.getElementById('selectForm').submit();
    }
}

function confirmDeletion(crn, name) {
    if (confirm("Are you sure you want to delete " + name + " ?")) {
        document.getElementById('delstudentId').value = crn;
        document.getElementById('deleteForm').submit();
    }
}