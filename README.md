
## Important aspects
The project has now a Makefile for easiness of usage.
The dockerized project uses symfony-docker (based on FrankenPHP), which is kind of recommended by Symfony https://symfony.com/doc/current/setup/docker.html, which provides a quick way to start the project.

## Start the project
1. Run `make start` to build and start the project: This will install all the dependencies and start the containers.
2. `make test` will run all the unit tests.
3. Run `make sync` to run the code

### Why does it fail? 
The database is not configured. I configured a SQLite (in memory) database, but I did not create any migration for it. 
At some extent, I felt like I was adding too much Symfony to the test, and I'm not sure if it was the goal, nor making
the database work. So instead, I separated the concerns of the classes and made assertions in the tests, when a repository
save method is called. 

## How would I approach a refactor like this?
1. Integration tests: Setting up integration tests for the public interface of DoctorSlotsSynchronizer, checking database inserts.
2. Interface creation: As I did, create a new interface.
3. Create a new class that implements the interface, with the new refactored code. 
4. Switching between one implementation or the other in the Integration test should not have any difference. Tests should pass for both. 
5. Once that happens, and added unit tests, switch using a feature flag if needed. 

## Improvements?
Many. For instance, I can think about DI, some Factories (usually I like to keep it simple until needed), configurations in yaml, etc.
