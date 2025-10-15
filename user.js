// user.js
document.addEventListener('DOMContentLoaded', () => {
  const userProfileImage = document.querySelector('.user-profile-image');
  const userProfileInfo = document.querySelector('.user-profile-info');

  // Fetch user data from the backend and populate the page
  fetchUserData()
	.then((userData) => {
	  userProfileImage.src = userData.avatarUrl;
	  userProfileInfo.innerHTML = `
		<label>Username:</label>
		<p>${userData.username}</p>
		<label>Email:</label>
		<p>${userData.email}</p>
		<label>Name:</label>
		<p>${userData.firstName} ${userData.lastName}</p>
		<!-- Add more user information -->
	  `;
	})
	.catch((error) => {
	  console.error('Error fetching user data:', error);
	});

  // Add event listeners for user profile updates, etc.
});

async function fetchUserData() {
  const response = await fetch('/api/user');
  return await response.json();
}