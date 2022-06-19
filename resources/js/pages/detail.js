import React from "react";
//import TextareaAutosize from 'react-textarea-autosize';
import '../../css/app.css';
import axios from "../components/axios";

class Detail extends React.Component
{
    constructor(props) {
        super(props);
        this.state = JSON.parse(sessionStorage.getItem("crawl_ret"));
        //console.log(this.state);
    };

    handleBackClick() {
        window.location.pathname = "/home";
    };

    render()
    {
        return (
            <div className="App">
                <header className="App-header">
                    <div>
                        <button onClick={this.handleBackClick}>
                            back to home
                        </button>
                    </div>
                    <img 
                        src={this.state.display}
                        alt="new"
                        width="960"
                        height="auto"
                    />
                    <a href={this.state.url}> {this.state.title} </a>
                    <label> {this.state.description} </label>
                    <div>
                        <textarea
                            readOnly
                            value={this.state.body}
                            cols="100"
                            rows="50"
                        />
                    </div>
                    <div>
                        <button onClick={this.handleBackClick}>
                            back to home
                        </button>
                    </div>
                </header>
            </div>
        );
    };
};

export default Detail;