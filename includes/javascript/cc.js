if(cwvpb_ccdata.grab_cc_check!=1){
	let xhr = new XMLHttpRequest();
	xhr.open("POST", cwvpb_ccdata.ajaxurl, true);
	xhr.setRequestHeader("Accept", "application/json");
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=utf-8");

	xhr.onload = () => console.log(xhr.responseText);

	let data = {
	  "action": "cc_call",
	  "security_nonce": cwvpb_ccdata.cc_nonce,
	  "current_url": cwvpb_ccdata.current_url,
	};
	const toQueryString = obj => "".concat(Object.keys(obj).map(e => `${encodeURIComponent(e)}=${encodeURIComponent(obj[e])}`).join("&"));
	xhr.send(toQueryString(data));
}