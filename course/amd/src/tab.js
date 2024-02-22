
alert("I am an alert box!");
document.addEventListener('DOMContentLoaded', function() {
        // Add your JavaScript functionality here
        // For example, code to switch between tabs
        const weakButton = document.getElementById('weakButton');
        const allButton = document.getElementById('allButton');
        const weakTopics = document.getElementById('weakTopics');
        const allTopics = document.getElementById('allTopics');
    
        weakButton.addEventListener('click', function() {
            weakButton.classList.add('selected');
            allButton.classList.remove('selected');
            weakTopics.style.display = 'block';
            allTopics.style.display = 'none';
        });
    
        allButton.addEventListener('click', function() {
            allButton.classList.add('selected');
            weakButton.classList.remove('selected');
            allTopics.style.display = 'block';
            weakTopics.style.display = 'none';
        });
    });
