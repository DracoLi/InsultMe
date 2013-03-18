# InsultMe

Insult me is an api written in php that generates insults based on a query. The insults are generated by searching for that query on youtube and analyzing the comments on top search results.

The result is surprising in that the results generated are pretty well. The results roughly responds to the insults that you would get if you were saying "I like [query you entered through the api]". For example if I entered 'Justin Bieber' as the query I would probably get 'You have no sense in music' etc back as the insults.

In a separate release I also added the ability to like a certain insult. The data is stored in an mysql database. You configure the database through the credentials.php file.