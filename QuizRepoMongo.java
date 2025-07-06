package com.uday.QuizQuestion;

import org.springframework.data.mongodb.repository.MongoRepository;

public interface QuizRepoMongo extends MongoRepository<Quiz,Integer>{

}
