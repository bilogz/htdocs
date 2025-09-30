function updateProfilePicture(input) {
    if (input.files && input.files[0]) {
        const formData = new FormData();
        formData.append('profile_picture', input.files[0]);

        fetch('update_profile_pic.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Update all profile picture elements on the page
                const profilePics = document.querySelectorAll('.profile-pic');
                profilePics.forEach(pic => {
                    pic.src = data.new_path;
                });
                
                // Show success message
                alert('Profile picture updated successfully!');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the profile picture.');
        });
    }
}

// Add event listener to profile picture input when the page loads
document.addEventListener('DOMContentLoaded', function() {
    const profilePicInput = document.getElementById('profile-pic-input');
    if (profilePicInput) {
        profilePicInput.addEventListener('change', function() {
            updateProfilePicture(this);
        });
    }
}); 