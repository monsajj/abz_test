<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: left;
        }
        img {
            width: 70px;
            height: 70px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <h1>User Management</h1>

    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Position ID</th>
            <th>Position</th>
            <th>Registration Timestamp</th>
            <th>Photo</th>
        </tr>
        </thead>
        <tbody id="users"></tbody>
    </table>
    <button id="showMore">Show more</button>


    <h2>Positions List</h2>
    <ul id="positionsList"></ul>

    <h2>Get Registration Token</h2>
    <button id="getToken">Get Token</button>
    <div id="tokenDisplay" style="margin-top: 10px;"></div>

    <h2>Add New User</h2>
    <form id="userForm">
        <input type="text" id="name" placeholder="Name">
        <input type="text" id="email" placeholder="Email">
        <input type="text" id="phone" placeholder="Phone (+380)">
        <input type="text" id="position_id" placeholder="Position ID">
        <input type="file" id="photo" accept="image/jpeg,image/png">
        <input type="text" id="token" value="" placeholder="Token">
        <button type="submit">Add User</button>
    </form>

    <h2>Get User by ID</h2>
    <input type="text" id="userId" placeholder="Enter User ID">
    <button id="getUser">Get User</button>
    <div id="userDetails" style="margin-top: 10px;"></div>

    <script>
        let currentPage = 1;
        const usersPerPage = 6;

        //Load users into table
        function loadUsers(page) {
            fetch(`/api/v1/users?page=${page}&count=${usersPerPage}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const usersDiv = document.getElementById('users');
                        data.users.forEach(user => {
                            const userRow = document.createElement('tr');
                            userRow.innerHTML = `
                                    <td>${user.id}</td>
                                    <td>${user.name}</td>
                                    <td>${user.email}</td>
                                    <td>${user.phone}</td>
                                    <td>${user.position_id}</td>
                                    <td>${user.position}</td>
                                    <td>${user.registration_timestamp}</td>
                                    <td><img src="${user.photo}" alt="${user.name}'s photo"></td>
                                `;
                            usersDiv.appendChild(userRow);
                        });

                        // Handle showing "Show more" button
                        document.getElementById('showMore').style.display = data.links.next_url ? 'block' : 'none';
                    }
                });
        }

        // Load initial users
        loadUsers(currentPage);

        // Show more button click event
        document.getElementById('showMore').addEventListener('click', () => {
            currentPage++;
            loadUsers(currentPage);
        });

        // Get the registration token
        document.getElementById('getToken').addEventListener('click', () => {
            fetch('/api/v1/token', {
                method: 'GET',
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('token').value = data.token;
                        document.getElementById('tokenDisplay').innerText = 'Token: ' + data.token;
                    } else {
                        alert('Failed to get token: ' + data.message);
                    }
                });
        });

        // Register user
        document.getElementById('userForm').addEventListener('submit', (e) => {
            e.preventDefault();

            const formData = new FormData();
            formData.append('name', document.getElementById('name').value);
            formData.append('email', document.getElementById('email').value);
            formData.append('phone', document.getElementById('phone').value);
            formData.append('position_id', document.getElementById('position_id').value);
            formData.append('photo', document.getElementById('photo').files[0]);

            fetch('/api/v1/users', {
                method: 'POST',
                headers: {
                    'Token': document.getElementById('token').value,
                },
                body: formData,
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('User successfully registered. ID: ' + data.user_id);
                        document.getElementById('userForm').reset();
                    } else {
                        let errorMessage = 'Error: ' + data.message;
                        if (data.fails) {
                            errorMessage += '\nValidation Fails:\n';
                            for (const [key, messages] of Object.entries(data.fails)) {
                                errorMessage += key + ': ' + messages.join(', ') + '\n';
                            }
                        }
                        alert(errorMessage);
                    }
                })
                .catch(error => {
                    console.error('Error during registration:', error);
                    alert('An error occurred while registering the user.');
                });
        });

        // Get user by ID
        document.getElementById('getUser').addEventListener('click', () => {
            const userId = document.getElementById('userId').value;
            if (!userId) {
                return alert('Please enter a user ID.');
            }

            fetch(`/api/v1/users/${userId}`)
                .then(response => response.json())
                .then(data => {
                    const userDetails = document.getElementById('userDetails');
                    if (data.success) {
                        userDetails.innerHTML = `
                                <strong>User ID:</strong> ${data.user.id}<br>
                                <strong>Name:</strong> ${data.user.name}<br>
                                <strong>Email:</strong> ${data.user.email}<br>
                                <strong>Phone:</strong> ${data.user.phone}<br>
                                <strong>Position ID:</strong> ${data.user.position_id}<br>
                                <strong>Position:</strong> ${data.user.position}<br>
                                <strong>Photo:</strong> <img src="${data.user.photo}" alt="${data.user.name}'s photo" style="width:70px;height:70px;">
                            `;
                    } else {
                        userDetails.innerHTML = '';
                        let errorMessage = 'Error: ' + data.message;
                        if (data.fails) {
                            errorMessage += '\nValidation Fails:\n';
                            for (const [key, messages] of Object.entries(data.fails)) {
                                errorMessage += key + ': ' + messages.join(', ') + '\n';
                            }
                        }
                        alert(errorMessage);
                    }
                })
                .catch(error => {
                    console.error('Error fetching user:', error);
                    alert('An error occurred while fetching user details.');
                });
        });

        // Load the positions list
        function loadPositions() {
            fetch('/api/v1/positions')
                .then(response => response.json())
                .then(data => {
                    const positionsList = document.getElementById('positionsList');
                    positionsList.innerHTML = '';

                    if (data.success) {
                        data.positions.forEach(position => {
                            const listItem = document.createElement('li');
                            listItem.textContent = `ID: ${position.id}, Name: ${position.name}`;
                            positionsList.appendChild(listItem);
                        });
                    } else {
                        alert('Failed to load positions: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error fetching positions:', error);
                    alert('An error occurred while fetching positions.');
                });
        }

        // Load positions when the page loads
        document.addEventListener('DOMContentLoaded', loadPositions);

    </script>
</body>
</html>
