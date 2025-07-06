package com.uday.QuizQuestion;

import java.util.List;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Component;

@Component
public class QuestionService {
@Autowired
	QuestionRepo repo;
	public List<Qestion> getAllQuestions() {
		// TODO Auto-generated method stub
		List<Qestion> questions=repo.findAll();
		return questions;
	}
	public String addQuestions(List<Qestion> qq) {
		// TODO Auto-generated method stub
		repo.saveAll(qq);
		return "Question Addition Done";
	}

}
