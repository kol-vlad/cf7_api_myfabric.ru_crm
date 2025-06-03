
add_action('wp_footer', function () {
    ?>
<script> 
document.addEventListener('DOMContentLoaded', function () {
    const name = 'your-name';
    const email = 'your-email';
    const phone = 'mask-496';
    const surname = 'surname';
    const father_name = 'father_name';
    const myfabriccid = '11';

	let allFiles = [];let FormId = '';
	const forms = document.querySelectorAll('.wpcf7-form');
  ///сессия в локал сторадж
	const timestamp = Date.now().toString();
    const randomDigits = Math.random().toString().replace(/\D/g, '').substr(0, 5);
	const sessionId = timestamp + randomDigits;
    const expiresAt = Date.now() + 999 * 60 * 60 * 1000; 
  
localStorage.setItem("session_data", JSON.stringify({
  value: sessionId,
  expires: expiresAt
}));
	
const stored = localStorage.getItem("session_data");

if (stored) {
  const data = JSON.parse(stored);
  if (Date.now() < data.expires) {
   const sessionId = data.value;
  } else {
    localStorage.removeItem("session_data"); // очищаем устаревшее значение
  }
} 
	
	
	forms.forEach(form => {
        const fileInputs = form.querySelectorAll('input[type="file"]');
        const filesContainer = form.querySelector('.ffiles');
       
			
        fileInputs.forEach(input => {
    input.addEventListener('change', async function () {
		input.parentElement.classList.add('rainbow'); 
        for (let file of input.files) {
            const result = await uploadFile(file, sessionId);

            if (Array.isArray(result)) {
                allFiles.push(...result);
            } else {
                allFiles.push(result); 
            }
        }

    
        // Очистка контейнера
        if (filesContainer) filesContainer.innerHTML = '';

         const fileBlockHeader  = document.createElement('span');
            fileBlockHeader.textContent = 'Прикрепленные файлы:';
            filesContainer.appendChild(fileBlockHeader);
        // Рендер всех файлов
        allFiles.forEach(fileObj => {
            const fileBlock = document.createElement('div');
            fileBlock.classList.add('file-item');
           

            const fileName = document.createElement('span');
            fileName.textContent = fileObj.name || fileObj.path.split('/').pop();

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.textContent = '✖';
            removeBtn.classList.add('remove-file');

            // Удаление файла
            removeBtn.addEventListener('click', () => {
            

                if (filesContainer) filesContainer.removeChild(fileBlock);

                // Удалить файл из массива по path
                allFiles = allFiles.filter(f => f.path !== fileObj.path);
                 if (allFiles.length === 0) filesContainer.removeChild(fileBlockHeader);
               console.log(allFiles);
            });

            fileBlock.appendChild(fileName);
            fileBlock.appendChild(removeBtn);
        
      
       if (filesContainer) filesContainer.appendChild(fileBlock);


        });

     
        input.parentElement.classList.remove('rainbow'); 
       
        input.value = '';console.log(allFiles);
    });
});			
    });
	
	
	// заполнение и отправка draft
	
forms.forEach(form => {

  const inputs = form.querySelectorAll(
    'input[type="text"][aria-required="true"], input[type="email"][aria-required="true"], input.wpcf7-mask '
  );
 
  form.addEventListener('input', async () => {

    let allFilled = true;
	
    const values = [];

    inputs.forEach(input => {
      const value = input.value.trim();
      values.push({ name: input.name, value });

      if (!value) {
        allFilled = false;
      }
    });
    
	   const draftData = new FormData();
      draftData.append('name', values.find(v => v.name === name)?.value || '');
      draftData.append('email', values.find(v => v.name === email)?.value || '');
      draftData.append('phone', values.find(v => v.name === phone)?.value || '');
      draftData.append('surname', values.find(v => v.name === surname)?.value || '');
      draftData.append('father_name', values.find(v => v.name === father_name)?.value || '');
	  draftData.append('description', form.querySelector('textarea').value || '');
	  draftData.append('status', 'draft');
	  draftData.append('session_id', sessionId);
	  
	  
    if (allFilled &&  (FormId ==='' || FormId === undefined ) ) {
     
     console.log(values+form.querySelector('textarea').value);
      try {
        const responsedraft = await fetch('https://app.myfabric.ru/api/order-requests', {
          method: 'POST',
		  headers: {
    'myfabric-cid': myfabriccid,
    'accept': 'application/json'
  },
          body: draftData
        });
        const result = await responsedraft.json();
      console.log(result);

  FormId = result?.orderRequest?.id;
	console.log('Order ID:', FormId);
      } catch (error) {
        console.error('Ошибка отправки:', error);
      }
    } else if  (allFilled && (FormId !=='' || FormId !== undefined ))  {
  
  
     console.log(values);
      try {
const draftData = {
  name: values.find(v => v.name === name)?.value || '',
  email: values.find(v => v.name === email)?.value || '',
  phone: values.find(v => v.name === phone)?.value || '',
  surname: values.find(v => v.name === surname)?.value || '',
  father_name: values.find(v => v.name === father_name)?.value || '',
  description: form.querySelector('textarea')?.value || '1',
  status: 'draft',
  session_id: sessionId
};
        const responsedraft = await fetch(`https://app.myfabric.ru/api/order-requests/${FormId}/draft`, 
						{
          method: 'PUT',
		  headers: {
    'Content-Type': 'application/json',
    'accept': 'application/json',
    'myfabric-cid': myfabriccid
  },
       body: JSON.stringify(draftData)
        });
        const result = await responsedraft.json();
 
      } catch (error) {
        console.error('Ошибка отправки:', error);
      }
  
	}
	
	
  });
	
	// отправка формы
	form.addEventListener('submit',  function(event) {
   if (FormId !=='' || FormId !== undefined ) {
   const filesArray = allFiles.map(({ status, uploaded, file_size, ...rest }) => ({
  ...rest,
  size: rest.size ?? file_size
}));
    
    try {
        const requestfiles =  fetch(`https://app.myfabric.ru/api/order-requests/${FormId}/files`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'accept': 'application/json',
                'myfabric-cid': myfabriccid
            },
            body: JSON.stringify({
                files: filesArray,
                session_id: sessionId
            })
        });
    } catch (error) {
        console.error('Ошибка при отправке:', error);
    }

	async function sendRequest() {
  try {
    const response = await fetch(`https://app.myfabric.ru/api/order-requests/${FormId}/send`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'accept': 'application/json',
        'myfabric-cid': myfabriccid
      },
      body: JSON.stringify({
        session_id: sessionId
      })
    });

    if (response.ok) {
      // Всё успешно
     // console.log('Успешно отправлено');
      const filesContainer = form.querySelector('.ffiles');
      if (filesContainer) filesContainer.innerHTML = '';
    } else {
      console.error('Ошибка отправки, код ответа:', response.status);
    }

  } catch (error) {
    console.error('Ошибка при отправке:', error);
  }
}
 
sendRequest(); }
});
});
	
	
    const MAX_PART_SIZE = 6 * 1024 * 1024;

		const uploadFile = async (file, sessionId) => {
			if (file.size <= MAX_PART_SIZE) {
				return await uploadSingle(file, sessionId);
			} else {
				return await uploadInParts(file, sessionId);
			}
		};

    const uploadSingle = async (file, sessionId) => {
        const formData = new FormData();
        formData.append('session_id', sessionId);
        formData.append('file', file);

          const response = await fetch('https://app.myfabric.ru/api/public-upload', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
		console.log(result);
		
        return result;
		
    };

const uploadInParts = async (file, sessionId) => {
    const partSize = MAX_PART_SIZE;
    const partCount = Math.ceil(file.size / partSize);
  let parts = null;

    console.log(`[init] Запуск multipart-загрузки файла "${file.name}" (${file.size} байт), частей: ${partCount}`);

  
    const initResponse = await fetch('https://app.myfabric.ru/api/public-upload/multipart', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            session_id: sessionId,
            filename: file.name,
            file_type: file.type,
        }),
    });

    const initResult = await initResponse.json();
    const uploadId = initResult.UploadId;
    const key = initResult.Key;

 
    for (let i = 0; i < partCount; i++) {
        const partNumber = i + 1;
        const partBlob = file.slice(i * partSize, Math.min((i + 1) * partSize, file.size));

        const formData = new FormData();
        formData.append('part', partBlob, file.name);
        formData.append('PartNumber', partNumber);
        formData.append('Key', key);

        console.log(`[upload] Загружаем часть ${partNumber}/${partCount} (${partBlob.size} байт)`);

        const uploadResponse = await fetch(`https://app.myfabric.ru/api/public-upload/multipart/${uploadId}`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
            },
            body: formData,
        });
        let uploadResultText = null;

		 uploadResultText = await uploadResponse.text();
          //  console.log(`[debug] Ответ от сервера на часть ${partNumber}:`, uploadResultText);	
    }

    const response = await fetch(`https://app.myfabric.ru/api/public-upload/multipart/${uploadId}?Key=${key}`);
    const data = await response.json();
    parts = data.parts;   // присваиваем глобальной переменной
    console.log('Parts получены:', parts);
 
	
    const completeResponse = await fetch(`https://app.myfabric.ru/api/public-upload/multipart/${uploadId}/complete`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            Key: key,
            Parts: parts,
        }),
    });

    const completeResult = await completeResponse.json();
    const fileInfo = completeResult.file;
	console.log(completeResult.file);
  //  console.log(`[complete] Загрузка завершена. Получен путь: ${fileInfo?.url || fileInfo?.path}`);

    return fileInfo;
};
     
});
</script>
    <?php
});
