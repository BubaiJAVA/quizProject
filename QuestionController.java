package com.uday.QuizQuestion;

import java.util.ArrayList;
import java.util.List;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;

import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

@RestController
@RequestMapping("Question")
public class QuestionController {
	@Autowired
	QuestionService service;
	@RequestMapping("/home")
	public ResponseEntity<String> homePage(){
		String s="This Is Home Page";
		return new ResponseEntity<>(s,HttpStatus.OK);
	}

	@GetMapping("/getAllQuestion/{admin}")
	public ResponseEntity<List<Qestion>> getAllQuestion(@PathVariable("admin") String adminCode){
		List<Qestion> questions1=new ArrayList<>();
		if(adminCode.equals("Uday1234")) {
		List<Qestion> questions=service.getAllQuestions();
		 return new ResponseEntity<>(questions,HttpStatus.OK);
		}
		else {
			 return new ResponseEntity<>(questions1,HttpStatus.OK);
		}
	}
	
	@PostMapping("/addQuestions/{admin}")
	public String addQuestions(@RequestBody List<Qestion> qq,@PathVariable("admin") String admin) {
		if(admin.equals("Uday12345")) {
		String ss=service.addQuestions(qq);
		return ss;
		}
		else {
			String ss="You are not Admin";
			return ss;
		}
	}
	
	
}
