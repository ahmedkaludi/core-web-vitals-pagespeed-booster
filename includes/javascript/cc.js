
let xhr = new XMLHttpRequest();
xhr.open("POST", cwvpb_ccdata.ajaxurl);

xhr.setRequestHeader("Accept", "application/json");
xhr.setRequestHeader("Content-Type", "application/json");

xhr.onload = () => console.log(xhr.responseText);

let data = {
  "action": "cc_call",
  "security_nonce": cwvpb_ccdata.cc_nonce,
  "current_url": cwvpb_ccdata.current_url,
};

xhr.send(data);