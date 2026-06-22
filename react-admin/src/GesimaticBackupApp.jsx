import { useState, useEffect } from 'react'
import { gt } from './helpers/gt.js' 

export const GesimaticBackupApp = () => {

  // It gets the credentials for access to the API
  const { restUrl, nonce, isSuperAdmin } = gesimaticBackupAdmin;

    // State to store the class and the content of alerts
    const [alert,setAlert] = useState({class:'gsmtc-display-none' ,content:''});    

    const onSubmit = async (event) =>{
        event.preventDefault();

        setAlert({class:'gsmtc-notice gsmtc-notice-warning',content:gt('creating_backup_download','Creating backup and downloading file.. Please wait')});

        // create the header with the nonce token
        const headers = new Headers({
            'X-WP-Nonce': nonce 
        })    

        // create the FormData to store the action of query
        let apiData = new FormData();
        apiData.append('action','get_backup_download');

        try {

            // send the query to the api endpoint
            const response = await fetch(restUrl, {
                method: 'POST',
                headers: headers,
                body: apiData,
            });

            // get the response header
            const contentType = response.headers.get('Content-Type');

            if (contentType.includes('application/json')) {
                // Manejar error
                const errorData = await response.json();
            //    console.log('errorData :',errorData);
                setAlert({class:'gsmtc-notice gsmtc-notice-error',content:errorData.message});
            } else {
                // to get the file name from download
                const contentDisposition = response.headers.get("Content-Disposition");
                let filename = "error.txt"; // Default filename
            
                if (contentDisposition) {
                    const match = contentDisposition.match(/filename="?([^"]+)"?/);
                    if (match && match[1]) {
                        filename = match[1]; // Extract the filename
                    }
                }
                // Manage the file
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;                    
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);

                setAlert({class:'gsmtc-notice gsmtc-notice-success',content:gt('backup_downloaded_successfully','The backup file has been downloaded successfully')});
                setTimeout(() => {setAlert({class:'gsmtc-display-none',content:''})},7000);
            }

        }catch (error) {
//                console.log("Download error :",error);
                setAlert({class:'gsmtc-notice gsmtc-notice-error',content:gt('error_downloading_backup','Error downloading the backup file')});
        }
        

/*
        const downloadData = await downloadFile(restUrl, nonce, 'get_backup_download');

        if (downloadData){
            setAlert({class:'gsmtc-notice gsmtc-notice-success',content:gt('backup_downloaded_successfully','The backup file has been downloaded successfully')});
/*
            if (isHttps){
                const downloadInstaller = await downloadFile(restUrl, nonce, 'get_backup_installer');
    
                if (downloadInstaller){
                    setAlert({class:'gsmtc-notice gsmtc-notice-success',content:gt('backup_installer_downloaded_successfully','The backup and the installer files has been downloaded successfully')});
                    setTimeout(() => {setAlert({class:'gsmtc-display-none',content:''})},7000);
                } else {
                    setAlert({class:'gsmtc-notice gsmtc-notice-error',content:gt('error_downloading_installer','Error downloading the installer file')});
                }
            }  */
/*        } else {
                setAlert({class:'gsmtc-notice gsmtc-notice-error',content:gt('error_downloading_backup','Error downloading the backup file')});

        }
  */  
    }
    return (
    <>
        <div className="wrap">
            <h2>{ gt('backup_page','Backup page') }</h2>
            <form onSubmit={onSubmit} >
                <p className='submit'>
                { ! (isSuperAdmin) && <input type="submit" name="submit-gsmtc-backup" id="submit-gsmtc-backup" className="button button-primary" value={ gt('download_backup','Download backup') } />  }

                { (isSuperAdmin) && <input type="submit" name="submit-gsmtc-backup" id="submit-gsmtc-backup" className="button button-primary" value={ gt('download_backup','Download backup') } /> }

                </p>
            </form>
            { false &&
            <form onSubmit={onSubmitInstaller} >
                <p className='submit'>
                { ! (isSuperAdmin) && <input type="submit" name="submit-gsmtc-installer" id="submit-gsmtc-installer" className="button button-primary" value={ gt('download_installer','Download installer') } />  }

                { (isSuperAdmin) && <input type="submit" name="submit-gsmtc-installer" id="submit-gsmtc-installer" className="button button-primary" value={ gt('download_installer','Download installer') } /> }

                </p>
            </form>
            }
            <div className={alert.class}>
                <p>{alert.content}</p>
            </div>             
        </div>   
    </>
  )
}
