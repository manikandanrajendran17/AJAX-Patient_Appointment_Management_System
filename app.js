const tableBody=document.getElementById("appointmentTableBody");
const form=document.getElementById("appointmentForm");

let csrfToken="";

async function loadCsrfToken(){

    const response=await fetch("csrf.php");
    const data=await response.json();
    csrfToken=data.csrf_token;

}


async function loadAppointments(){

    try{

        const response=await fetch("api.php");
        const appointments=await response.json();
        console.log('Appointments:', appointments);

        tableBody.innerHTML="";

        appointments.forEach((appointment, index)=>{

            tableBody.innerHTML+=`
            <tr>
                <td>${index + 1}</td>
                <td>${appointment.patient_name}</td>
                <td>${appointment.email}</td>
                <td>${appointment.mobile}</td>
                <td>${appointment.doctor_name}</td>
                <td>${appointment.appointment_date}</td>
                <td>${appointment.appointment_time}</td>

                <td>
                    <select onchange="updateStatus(${appointment.id},this.value)">
                        <option value="Pending" ${appointment.status==="Pending"?"selected":""}>Pending</option>
                        <option value="Confirmed" ${appointment.status==="Confirmed"?"selected":""}>Confirmed</option>
                        <option value="Cancelled" ${appointment.status==="Cancelled"?"selected":""}>Cancelled</option>
                    </select>
                </td>

                <td>
                    <button onclick="editAppointment(${appointment.id})">Edit</button>
                    <button onclick="deleteAppointment(${appointment.id})">Delete</button>
                </td>
            </tr>
            `;

        });

    }catch(error){

        console.log(error);

    }

}

async function editAppointment(id){

    try{

        const response=await fetch("api.php");
        
        const appointments=await response.json();

        const appointment=appointments.find(
            item=>item.id==id
        );

        document.getElementById("appointmentId").value=appointment.id;
        document.getElementById("patientName").value=appointment.patient_name;
        document.getElementById("email").value=appointment.email;
        document.getElementById("mobile").value=appointment.mobile;
        document.getElementById("doctorName").value=appointment.doctor_name;
        document.getElementById("appointmentDate").value=appointment.appointment_date;
        document.getElementById("appointmentTime").value=appointment.appointment_time;

    }catch(error){

        console.log(error);

    }

}

form.addEventListener("submit",saveAppointment);

async function saveAppointment(e){ 

    e.preventDefault();

    const id=document.getElementById("appointmentId").value;

    const formData={
        id:id,
        patient_name:document.getElementById("patientName").value,
        email:document.getElementById("email").value,
        mobile:document.getElementById("mobile").value,
        doctor_name:document.getElementById("doctorName").value,
        appointment_date:document.getElementById("appointmentDate").value,
        appointment_time:document.getElementById("appointmentTime").value
    };

    const method=id ? "PUT" : "POST";

    try{

        const response=await fetch("api.php",{
           method:method,
            headers:{
                "Content-Type":"application/json",
                "X-CSRF-TOKEN":csrfToken
            },
            body:JSON.stringify(formData)
        });

        const result=await response.json();
        alert(result.message);
        form.reset();
        document.getElementById("appointmentId").value="";

        loadAppointments();

    }catch(error){

        console.log(error);

    }

}

async function deleteAppointment(id){

    const confirmDelete=confirm(
        "Are you sure you want to delete?"
    );

    if(!confirmDelete){
        return;
    }

    try{

        const response=await fetch("api.php",{
            method:"DELETE",
            headers:{
                "Content-Type":"application/json",
                "X-CSRF-TOKEN":csrfToken
            },
            body:JSON.stringify({
                id:id
            })
        });

        const result=await response.json();

        alert(result.message);

        loadAppointments();

    }catch(error){

        console.log(error);

    }

}

async function updateStatus(id,status){

    try{

        const response=await fetch("api.php",{
            method:"PATCH",
            headers:{
                "Content-Type":"application/json",
                "X-CSRF-TOKEN":csrfToken
            },
            body:JSON.stringify({id:id,status:status})
        });

        const result=await response.json();

        alert(result.message);

        loadAppointments();

    }catch(error){

        console.log(error);

    }

}

window.onload=async()=>{

    await loadCsrfToken();

    await loadAppointments();

}