<style>
      /* From Uiverse.io by vinodjangid07 */ 
  .notificationCard {
    width: 220px;
    height: 280px;
    background: rgb(245, 245, 245);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px 35px;
    gap: 10px;
    box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.123);
    border-radius: 20px;
  }

  .bellIcon {
    width: 50px;
    margin: 20px 0px;
  }

  .bellIcon path {
    fill: rgb(168, 131, 255);
  }

  .notificationHeading {
    color: black;
    font-weight: 600;
    font-size: 0.8em;
  }

  .notificationPara {
    color: rgb(133, 133, 133);
    font-size: 0.6em;
    font-weight: 600;
    text-align: center;
  }

  .buttonContainer {
    display: flex;
    flex-direction: column;
    gap: 5px;
  }

  .AllowBtn {
    width: 120px;
    height: 25px;
    background-color: rgb(168, 131, 255);
    color: white;
    border: none;
    border-radius: 20px;
    font-size: 0.7em;
    font-weight: 600;
    cursor: pointer;
  }

  .NotnowBtn {
    width: 120px;
    height: 25px;
    color: rgb(168, 131, 255);
    border: none;
    background-color: transparent;
    font-weight: 600;
    font-size: 0.7em;
    cursor: pointer;
    border-radius: 20px;
  }

  .NotnowBtn:hover {
    background-color: rgb(239, 227, 255);
  }

  .AllowBtn:hover {
    background-color: rgb(153, 110, 255);
  }

  .modal-overlay-container.active {
    opacity: 1;
    visibility: visible;
  }

  .modal-overlay-container{

    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
  }
</style>
<?php 
function constructNotificationModal($title, $message) {
    ?>
<div class="modal-overlay-container active" id="notificationModal">
    <div class="notificationCard">
        <p class="notificationHeading"><?php echo $title; ?></p>
        <p class="notificationPara"><?php echo $message; ?></p>
        <div class="buttonContainer">
            <button class="AllowBtn">Ok</button>
            <button class="NotnowBtn">Cancel</button>
        </div>
    </div>
</div>

<script>
    const notificationModal = document.getElementById('notificationModal');
    const allowBtn = document.querySelector('.AllowBtn');
    const notNowBtn = document.querySelector('.NotnowBtn');

    allowBtn.addEventListener('click', () => {
        // Handle the logic for allowing notifications here
        notificationModal.classList.remove('active');
    });

    notNowBtn.addEventListener('click', () => {
        // Handle the logic for not allowing notifications here
        notificationModal.classList.remove('active');
    });
</script>
<?php
}

?>