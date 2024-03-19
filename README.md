# Backend Code Sample

This project serves as a code sample to showcase development skills. Below is a guide for setting up and running the project.

## Installation Guide

### Step 1: Clone the Repository

Open your command line and run the following commands:

```bash
git clone https://github.com/kategarshyna/code-sample-backend.git <project folder>
cd <project folder>
```

Replace `<project folder>` with the desired name for the project folder.

### Step 2: Start Docker Compose

Make sure you have Docker and Docker Compose installed on your machine. If not, you can find installation instructions [here](https://docs.docker.com/compose/install/).

In the project directory, run:

```bash
docker-compose up
```

This command will build the Docker images and start the containers.

### Step 3: Open the Application

Once the containers are up and running, open your browser and navigate to [http://localhost:8080/](http://localhost:8080/).

You should now see the backend application running.

## Project Information

This project serves as a code sample, providing essential configurations and dependencies for running a Symfony-based application.

### User API Functionality

The project includes robust user API functionality, which is well-documented and can be conveniently tested via Swagger UI. Simply visit [http://localhost:8080/api/doc](http://localhost:8080/api/doc) to explore and test the various API endpoints.

### Console Command - Sending Newsletter

#### Usage

To send a newsletter to all active users created during the last week, a console command named `send:newsletter` has been implemented. Follow these steps:

1. Connect to the PHP Docker container using the command:
    ```bash
    docker-compose exec php sh
    ```

2. Run the following command inside the container:
    ```bash
    php bin/console send:newsletter
    ```

3. If you have already registered some users via the API, you can check the sent newsletters in the Mailer container's web version: [http://localhost:8025/](http://localhost:8025/).


### Technologies Used

- PHP 7.4
- Symfony Framework v4.4
- Docker
- Nginx
- MySQL

### Project Structure

The project has a standard Symfony structure with configurations for Docker, Nginx, and MySQL in the `docker` folder.

### Important Notes

- Make sure to have Docker and Docker Compose installed before running the project.
- The application should be accessible at [http://localhost:8080/](http://localhost:8080/).
- If you encounter any issues, check the Docker logs and ensure that there are no port conflicts.

Feel free to reach out if you have any questions or need further assistance.
