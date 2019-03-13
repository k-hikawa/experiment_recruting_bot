window.onload = function() {
	const create_experiment_radio = document.getElementById("create_experiment_radio");
	const send_message_radio = document.getElementById("send_message_radio");
	const create_experiment_form = document.getElementById("create_experiment_form");
	const send_message_form = document.getElementById("send_message_form");
	const experiment_select_all = document.getElementById("experiment_select_all");
	const experiment_selects = new Array(6);
	for (var i = 0; i < experiment_selects.length; ++i) {
		var num = i + 1;
		experiment_selects[i] = document.getElementById("experiment_select_" + num);
	}
	const message_select_all = document.getElementById("message_select_all");
	const message_select_experiment1 = document.getElementById("message_select_experiment1");
	const message_select_experiment2 = document.getElementById("message_select_experiment2");

	create_experiment_radio.onclick = function() {
		create_experiment_form.style.display = "block";
		send_message_form.style.display = "none";
	}
	
	send_message_radio.onclick = function() {
		send_message_form.style.display = "block";
		create_experiment_form.style.display = "none";
	}

	// 実験作成のユーザ全て選択
	experiment_select_all.onclick = function() {
		experiment_user_checkbox('experiment_user_checkbox');
	}

	experiment_selects[0].onclick = function() {
		experiment_user_checkbox('experiment_user_checkbox1');
	}

	experiment_selects[1].onclick = function() {
		experiment_user_checkbox('experiment_user_checkbox2');
	}

	experiment_selects[2].onclick = function() {
		experiment_user_checkbox('experiment_user_checkbox3');
	}

	experiment_selects[3].onclick = function() {
		experiment_user_checkbox('experiment_user_checkbox4');
	}

	experiment_selects[4].onclick = function() {
		experiment_user_checkbox('experiment_user_checkbox5');
	}

	experiment_selects[5].onclick = function() {
		experiment_user_checkbox('experiment_user_checkbox6');
	}

	
	

	// 実験メッセージのユーザ全てを選択
	message_select_all.onclick = function() {
		experiment_user_checkbox('message_user_checkbox');
	}

	// 実験中の人だけを選択
	message_select_experiment1.onclick = function() {
		experiment_user_checkbox('message_user_checkbox1');
	}

	// 実験完了の人だけを選択
	message_select_experiment2.onclick = function() {
		experiment_user_checkbox('message_user_checkbox2');
	}

	// 実験者が選ばれた時のイベント
	/*document.getElementById("experiment_client").onchange = function() {
		var client = this.value;
		var xhr = new XMLHttpRequest();
		xhr.open('GET', 'https://hikawa.nkmr.io/LINEBOT/lab_experiment/get_experiment_title.php?client='+client);
		xhr.send();
 
		xhr.onreadystatechange = function() {
			if(xhr.readyState === 4 && xhr.status === 200) {
				const experiment_select = document.getElementById("experiment_select");
				const experiment_user_table = document.getElementById("experiment_user");
				experiment_user_table.innerHTML = "<tr><th></th><th>ステータス</th><th>学年</th><th>性別</th><th>年齢</th><th>名前</th><th>アイコン</th><th>ひとこと</th><th>実験依頼受託数</th><th>実験依頼達成数</th><th>最終利用日時</th></tr>";
				const experiment_data = JSON.parse(xhr.responseText);
				experiment_select.innerHTML = "<option>選んでください</option>";
				experiment_data.forEach(function(value) {
					experiment_select.innerHTML += "<option value='"+value['id']+"'>"+value['title']+"</option>";
				});
			}
		}
	}*/

	// 実験タイトルが選ばれた時のイベント
	document.getElementById("experiment_select").onchange = function() {
		var options = this.options;
		for (var i = 0; i < options.length; ++i) {
			if (options[i].selected) {
				document.getElementById("experiment_hidden_title").value = options[i].text;
			}
		}
		
		var ex_id = this.value;
		var xhr = new XMLHttpRequest();
		xhr.open('GET', 'https://hikawa.nkmr.io/LINEBOT/lab_experiment/get_experiment_users.php?ex_id=' + ex_id);
		xhr.send();
 
		xhr.onreadystatechange = function() {
			if (xhr.readyState === 4 && xhr.status === 200) {
				const experiment_user_table = document.getElementById("experiment_user");
				var data = xhr.responseText;
				// preserve newlines, etc - use valid JSON
				data = data.replace(/\\n/g, "\\n")
				.replace(/\\'/g, "\\'")
				.replace(/\\"/g, '\\"')
				.replace(/\\&/g, "\\&")
				.replace(/\\r/g, "\\r")
				.replace(/\\t/g, "\\t")
				.replace(/\\b/g, "\\b")
				.replace(/\\f/g, "\\f");
				// remove non-printable and other non-valid JSON chars
				data = data.replace(/[\u0000-\u0019]+/g,"");
				const users_data = JSON.parse(data);
				experiment_user_table.innerHTML = "<tr><th></th><th>ステータス</th><th>学年</th><th>性別</th><th>年齢</th><th>名前</th><th>アイコン</th><th>ひとこと</th><th>実験依頼受託数</th><th>実験依頼達成数</th><th>最終利用日時</th></tr>";
				users_data.forEach(function(value) {
					var status = statusTextConverter(Number(value.status));
					var class_name = statusClassConverter(Number(value.status));
					var grade = gradeTextConverter(Number(value.grade));
					var sex = sexTextConverter(Number(value.sex));
					var tempHTML = "";
					tempHTML += "<tr><td><input type='checkbox' class='"+class_name+"' name='user_id[]' value='"+value.id+"'></td>";
					if (value.status == 2) {
						tempHTML += "<td bgcolor='red'>"+status+"</td>";
					} else if (value.status == 1){
						tempHTML += "<td bgcolor='yellow'>"+status+"</td>";
					} else {
						 tempHTML += "<td>"+status+"</td>";
					}
					tempHTML += "<td>"+grade+"</td><td>"+sex+"</td><td>"+value.age+"</td><td>"+htmlspecialchars(value.name)+"</td><td>";
					if (value.icon !== null) {
						tempHTML += "<img src='"+value.icon+"' width=50>";
					}
					tempHTML += "</td><td>"+htmlspecialchars(value.message)+"</td><td>"+value.try+"</td><td>"+value.complete+"</td><td>"+value.login+"</td></tr>";
					experiment_user_table.innerHTML += tempHTML;
				});
			}
		}
	}
}

/*
 * まとめて選択ボタンのロジック
 *
 */
function experiment_user_checkbox(class_name) {
	var user_checkbox = document.getElementsByClassName(class_name);
	var change = false;
	for (var i = 0; i < user_checkbox.length; ++i) {
		if (!user_checkbox[i].checked) {
			user_checkbox[i].checked = true;
			change = true;
		}
	}
	if (!change) {
		for (var i = 0; i < user_checkbox.length; ++i) {
			user_checkbox[i].checked = false;
		}
	}
}

function statusTextConverter(value) {
	var status = "";
	switch (value) {
		case -1:
			status = "不参加";
			break;
		case 0:
			status = "未読";
			break;
		case 1:
			status = "実験中";
			break;
		case 2:
			status = "実験完了";
			break;
	}
	return status;
}

function statusClassConverter(value) {
	var class_name = "";
	switch (value) {
		case -1:
			class_name = "message_user_checkbox message_user_checkbox-1";
			break;
		case 0:
			class_name = "message_user_checkbox message_user_checkbox0";
			break;
		case 1:
			class_name = "message_user_checkbox message_user_checkbox1";
			break;
		case 2:
			class_name = "message_user_checkbox message_user_checkbox2";
			break;
	}
	return class_name;
}

function gradeTextConverter(value) {
	var grade = "";
	switch (value) {
		case 1:
			grade = "B1";
			break;
		case 2:
			grade = "B2";
			break;
		case 3:
			grade = "B3";
			break;
		case 4:
			grade = "B4";
			break;
		case 5:
			grade = "M1";
			break;
		case 6:
			grade = "M2";
			break;
		case 7:
			grade = "学生";
			break;
		case 8:
			grade = "教員";
			break;
		case 9:
			grade = "その他";
			break;
		default:
			grade = "未設定";
			break;
	}
	return grade;
}


function sexTextConverter(value) {
	var sex = "";
	switch (value) {
		case 1:
			sex = "男性";
			break;
		case 2:
			sex = "女性";
			break;
		case 3:
			sex = "その他";
			break;
		default:
			sex = "未設定";
			break;
	}
	return sex;
}

function htmlspecialchars(str){
  return (str + '').replace(/&/g,'&amp;')
                   .replace(/"/g,'&quot;')
                   .replace(/'/g,'&#039;')
                   .replace(/</g,'&lt;')
                   .replace(/>/g,'&gt;'); 
}